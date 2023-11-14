<?php

namespace App\Vendors\Scanner;

use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Distortion\PerspectiveDistortion;
use App\Vendors\Scanner\Marker\MarkRecognition;
use App\Vendors\Scanner\Scanner\Scanner;
use App\Vendors\Scanner\Schema\Schema;
use App\Vendors\Scanner\Services\AWS\Textract\DocumentAnalyzer;
use App\Vendors\Scanner\Traits\Scanner\ScannerTrait;
use Carbon\Carbon;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Intervention\Image\Facades\Image as InterventionImage;

class ImagickScanner
{
    use ScannerTrait;

    /** @var \Illuminate\Http\Request */
    private Request $request;

    /** @var \Psr\Log\LoggerInterface */
    private \Psr\Log\LoggerInterface $log;

    /** @var string */
    private string $svg;

    /** @var mixed */
    private mixed $settings;

    /** @var mixed */
    private mixed $userLocale;

    /** @var string */
    private string $appVersion;

    /** @var string */
    private string $scanDirectory;

    /** @var array */
    private array $languages = ['eng'];

    /** @var array */
    private array $blobReturns = [];

    /** @var array */
    private array $scanResults = [];

    /** @var string */
    private string $ocrEngine;

    /** @var int */
    private int $imageMaxWidth;

    /**
     * ImagickScanner constructor.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Request $request)
    {
        $this->setRequest($request);
        $scanDirectory = $this->getDirectoryPath(config('scanner.base_directory'), $request->ip(), Carbon::now());
        $this->setScanDirectory($scanDirectory);
        $this->initializeDirectories($scanDirectory);

        $this->initLog($this->getScanDirectory());

        $this->setSvg($request->get('svg'));
        $this->storeSvg($scanDirectory);

        $this->setOcrEngine($request->get('ocr_engine', 'tesseract'));
        $this->setBlobReturns($request);
        $this->setLanguages($request);
        $this->setAppVersion();
        $this->setImageMaxWidth();
    }

    /**
     * @return void
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     * @throws \thiagoalessio\TesseractOCR\TesseractOcrException
     */
    public function scan(): void
    {
        foreach ($this->getRequest()->get('images') as $imageData) {
            // Scanned page number
            $page = $imageData['page'];

            // Set page subdirectory
            $folder = $this->getScanDirectory() . '/' . 'page-' . $page;
            $this->createDirectory($folder);
            $this->initLog($folder);

            // Store scanned image
            $extension    = $this->getExtensionFromBlob($imageData['image'], true);
            $filename     = 'scan.' . $extension;
            $originalFile = $folder . '/' . $filename;
            $resizedFile = $folder . '/scan_resized.' . $extension;
                        
            File::put(storage_path($originalFile), $this->getBlobContents($imageData['image']));

            // Downsize the image if the width exceeds the max width setting
            $this->resizeImage($originalFile, $resizedFile, $this->getImageMaxWidth());

            // Init Imagick object with the original scanned image
            $image = new Imagick(storage_path($originalFile));

            // Create Schema from SVG & page number
            $schema = $this->createSchema($page, $folder);

            // Get original image markers
            $markers = $this->getMarkers($image, $schema, $folder, false);
            $config  = $this->createConfig($markers, $folder);

            // Apply perspective distortion on image to align the top and bottom markers
            $image = $this->distortImage($image, $config, $folder);

            // Get markers positions after the distortion
            $markers = $this->getMarkers($image, $schema, $folder);
            $config  = $this->createConfig($markers, $folder);

            $this->getLog()->info('OCR Engine: ' . $this->getOcrEngine());
            // Scan the image
            $scanner                = new Scanner($image, $schema, $config, storage_path($folder));
            $externalScannerResults = $this->getExternalScannerResults(storage_path($folder), $filename);
            $scanResults            = $scanner->scan($externalScannerResults);

            $this->storeScanResults($this->parseScanResults($scanResults), $folder);
            $this->addScanResult($page, $this->parseScanResults($scanResults));
        }

        if (!config('scanner.mode_debug')) {
            if (File::isDirectory(storage_path($this->getScanDirectory()))) {
                Storage::deleteDirectory(str_replace('app/', '', $this->getScanDirectory()));
            }
        }
    }

    /**
     * @param string $filepath
		 * @param string $filepath_resize
     * @param int    $width
     *
     * @return void
     */
    private function resizeImage(string $filepath, string $filepath_resize, int $width): void
    {
        $this->getLog()->debug('Resize image width setting: ' . $width);
        
        if ($width > 0) {
            $interventionImage = InterventionImage::make(storage_path($filepath));
            
            $img_width = InterventionImage::make(storage_path($filepath))->width();

						if($width < $img_width){
								$interventionImage->resize($width, null, function ($constraint) {
                $constraint->aspectRatio();                
            	})->rotate(-90)->save(storage_path($filepath));            	
						}
        }
    }

    /**
     * @param string $folder
     * @param string $filename
     *
     * @return array
     */
    public function getExternalScannerResults(string $folder, string $filename): array
    {
        $results = [];
        if ($this->getOcrEngine() === 'aws') {
            $documentAnalyzer = new DocumentAnalyzer($folder, $filename);
            $results          = $documentAnalyzer->analyze();
        }

        return $results;
    }

    /**
     * @param int    $page
     * @param string $folder
     *
     * @return \App\Vendors\Scanner\Schema\Schema
     */
    private function createSchema(int $page, string $folder): Schema
    {
        $schemaData = Schema::read($this->getSvg(), $this->getLanguages(), $page);

        return new Schema($schemaData, storage_path($folder));
    }

    /**
     * @param \Imagick                           $image
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     * @param string                             $folder
     * @param bool                               $drawMarks
     *
     * @return array
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    private function getMarkers(Imagick $image, Schema $schema, string $folder, bool $drawMarks = true): array
    {
        $markRecognition = new MarkRecognition($image, $schema, storage_path($folder));
        $markRecognition->recognizeMarkers($drawMarks);

        return $markRecognition->getMarkers();
    }

    /**
     * @param array  $markers
     * @param string $folder
     *
     * @return \App\Vendors\Scanner\Config\Config
     */
    private function createConfig(array $markers, string $folder): Config
    {
        $configData = json_decode(json_encode($markers));

        return new Config($configData, storage_path($folder));
    }

    /**
     * @param \Imagick                           $image
     * @param \App\Vendors\Scanner\Config\Config $config
     * @param                                    $folder
     *
     * @return \Imagick
     * @throws \ImagickException
     */
    private function distortImage(Imagick $image, Config $config, $folder): Imagick
    {
        $perspectiveDistortion = new PerspectiveDistortion($image, $config, storage_path($folder));

        return $perspectiveDistortion->distort();
    }

    /**
     * @param string $directory
     */
    private function initializeDirectories(string $directory): void
    {
        $scanDirectory = '';
        foreach (explode('/', $directory) as $subDirectory) {
            $scanDirectory .= $subDirectory . '/';
            $this->createDirectory($scanDirectory);
        }
    }

    /**
     * @param $directory
     */
    private function createDirectory($directory): void
    {
        if (!File::isDirectory(storage_path($directory))) {
            File::makeDirectory(storage_path($directory), 0775, true);
        }
    }

    /**
     * @return string
     */
    public function getScanDirectory(): string
    {
        return $this->scanDirectory;
    }

    /**
     * @param string $scanDirectory
     */
    public function setScanDirectory(string $scanDirectory): void
    {
        $this->scanDirectory = $scanDirectory;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function setLanguages(Request $request): void
    {
        if (is_array($request->get('data-user-locale'))) {
            foreach ($request->get('data-user-locale') as $locale) {
                $language = $this->getTesseractLanguage($locale);
                if (!empty($language)) {
                    $this->languages[] = $language;
                }
            }
        }

        if (is_string($request->get('data-user-locale'))) {
            $language = $this->getTesseractLanguage($request->get('data-user-locale'));
            if (!empty($language)) {
                $this->languages[] = $language;
            }
        }

        $this->languages = array_unique($this->languages);
    }

    /**
     * @param string $locale
     *
     * @return string|null
     */
    private function getTesseractLanguage(string $locale): ?string
    {
        return match (mb_strtolower($locale)) {
            'bg' => 'bul',
            default => null,
        };
    }

    /**
     * @param string $folder
     *
     * @return void
     */
    public function initLog(string $folder): void
    {
        $log = Log::build(
            [
                'driver' => 'single',
                'path'   => storage_path($folder) . '/' . 'scan.log',
                'level'  => config('scanner.log_mode'),
            ]
        );

        $this->setLog($log);
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }

    /**
     * @param \Psr\Log\LoggerInterface $log
     */
    public function setLog(\Psr\Log\LoggerInterface $log): void
    {
        $this->log = $log;
    }

    /**
     * @return \Illuminate\Http\Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getScanResults(): array
    {
        return $this->scanResults;
    }

    /**
     * @param array $scanResults
     */
    public function setScanResults(array $scanResults): void
    {
        $this->scanResults = $scanResults;
    }

    /**
     * @param array  $scanResults
     * @param string $folder
     */
    private function storeScanResults(array $scanResults, string $folder): void
    {
        $results = json_encode($scanResults, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        File::put(storage_path($folder) . '/' . 'results.json', $results);
    }

    /**
     * @param array $scanResults
     *
     * @return array
     */
    private function parseScanResults(array $scanResults): array
    {
        foreach ($scanResults as &$scanResult) {
            if (!empty($scanResult['type']) && !in_array($scanResult['type'], $this->getBlobReturns())) {
                $scanResult['image'] = [];
            }
        }

        return $scanResults;
    }

    /**
     * @param int   $page
     * @param array $scanResult
     */
    public function addScanResult(int $page, array $scanResult): void
    {
        $this->scanResults[] = [
            'page' => $page,
            'scan' => $scanResult,
        ];
    }

    /**
     * @return string
     */
    public function getSvg(): string
    {
        return $this->svg;
    }

    /**
     * @param string $svg
     */
    public function setSvg(string $svg): void
    {
        $this->svg = $svg;
    }

    /**
     * @param string $folder
     *
     * @return void
     */
    private function storeSvg(string $folder): void
    {
        File::put(storage_path($folder) . '/' . 'schema.svg', $this->getSvg());
    }

    /**
     * @return mixed
     */
    public function getSettings(): mixed
    {
        return $this->settings;
    }

    /**
     * @param mixed $settings
     */
    public function setSettings(mixed $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return mixed
     */
    public function getUserLocale(): mixed
    {
        return $this->userLocale;
    }

    /**
     * @param mixed $userLocale
     */
    public function setUserLocale(mixed $userLocale): void
    {
        $this->userLocale = $userLocale;
    }

    /**
     * @return array
     */
    public function getBlobReturns(): array
    {
        return $this->blobReturns;
    }

    /**
     * @param \Illuminate\Http\Request $request
     */
    public function setBlobReturns(Request $request): void
    {
        $settings    = $request->get('settings', []);
        $blobReturns = $settings['return_blob'] ?? [];

        foreach ($blobReturns as $type) {
            $this->blobReturns[] = mb_strtolower($type);
        }
    }

    /**
     * @return string
     */
    public function getAppVersion(): string
    {
        return $this->appVersion;
    }

    /**
     * Set app version from Schema
     */
    public function setAppVersion(): void
    {
        $document = new DOMDocument();
        $document->loadXML($this->getSvg());

        $svg              = $document->getElementsByTagName('svg');
        $this->appVersion = $svg[0]->getAttribute('data-app-version') ?? '';
    }

    /**
     * @return string
     */
    public function getOcrEngine(): string
    {
        return $this->ocrEngine;
    }

    /**
     * @param string $ocrEngine
     */
    public function setOcrEngine(string $ocrEngine): void
    {
        $this->ocrEngine = mb_strtolower(config('scanner.ocr_engine'));

        if (!empty($ocrEngine) && in_array(mb_strtolower($ocrEngine), ['aws', 'textract'])) {
            $hasAwsSettings = !empty(config('scanner.aws.key')) &&
                              !empty(config('scanner.aws.secret')) &&
                              !empty(config('scanner.aws.region')) &&
                              !empty(config('scanner.aws.version'));
            if ($hasAwsSettings) {
                $this->ocrEngine = 'aws';
            }
        }
    }

    /**
     * @return int
     */
    public function getImageMaxWidth(): int
    {
        return $this->imageMaxWidth;
    }

    /**
     * @return void
     */
    public function setImageMaxWidth(): void
    {
        $this->imageMaxWidth = (int)config('scanner.images.max_width');
    }
}
