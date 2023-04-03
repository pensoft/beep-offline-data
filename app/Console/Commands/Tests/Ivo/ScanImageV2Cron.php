<?php

namespace App\Console\Commands\Tests\Ivo;

use App\Console\PensoftOmrCommands;
use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Marker\MarkRecognition;
use App\Vendors\Scanner\Scanner\Scanner;
use App\Vendors\Scanner\Schema\Schema;
use Carbon\Carbon;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Imagick;
use Request;

class ScanImageV2Cron extends PensoftOmrCommands
{
    private string $path        = 'app/public/scans';
    private string $resultsPath = 'app/public/scans/results';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:ivo:scan-image-v2 {--file=} {--page=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Running tests for scanning an image';

    /**
     * InitializeOrders constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->writeOutput('TEST SCAN IMAGE COMMAND STARTED AT: ' . Carbon::now()->format('H:i:s'));

        try {
            $svgContents = file_get_contents(storage_path('app/public/scanner/configs/test_4_markers_v5.svg'));

            $filename = !empty($this->option('file')) ? $this->option('file') : 'scan';
            $filepath = storage_path($this->path . '/' . $filename . '.jpg');
            if (!File::exists($filepath)) {
                $this->writeOutput('FILE NOT FOUND');

                return;
            }

            $directory = str_replace('.', '-', Request::ip()) . '_' . Carbon::now()->format('Ymd-His');
            $this->writeOutput('Directory Path: ' . $directory);
            $this->initializeDirectories($directory);
            $this->resultsPath .= '/' . $directory;

            File::copy($filepath, storage_path($this->resultsPath . '/' . pathinfo($filepath, PATHINFO_BASENAME)));
            $filepath = storage_path($this->resultsPath . '/' . pathinfo($filepath, PATHINFO_BASENAME));

            $image      = new Imagick($filepath);
            $page       = !empty($this->option('page')) ? (int)$this->option('page') : 1;
            $schemaData = Schema::read($svgContents, ['eng'], $page);
            $schema     = new Schema($schemaData, storage_path($this->resultsPath));
            $this->writeLog('PAGE: ' . $page);

            $markRecognition = new MarkRecognition($image, $schema, storage_path($this->resultsPath));
            $markRecognition->recognizeMarkers();
            $markers = $markRecognition->getMarkers();

            $configData = json_decode(json_encode($markers));
            $config     = new Config($configData, storage_path($this->resultsPath));

            $scanner = new Scanner($image, $schema, $config, storage_path($this->resultsPath));
            //            $scanner->setDebugPath(storage_path($this->resultsPath));
            $scanner->scan();

            file_put_contents(
                storage_path($this->resultsPath . '/scan_results.json'),
                json_encode($scanner->getScanResults(),
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );
            //            $this->writeOutput(json_encode($scanner->getScanResults()));
        } catch (Exception $exception) {
            $this->writeOutput('[ScanImageV2Cron] Cron interrupted with exception', 'error');
            $this->writeOutput('Message: ' . $exception->getMessage(), 'error');
            $this->writeOutput('Line: ' . $exception->getLine(), 'error');
            $this->writeOutput('File: ' . $exception->getFile(), 'error');
            $this->writeOutput('Code: ' . $exception->getCode(), 'error');
            $this->writeOutput(print_r($exception->getCode(), true), 'error');
        }

        $this->writeOutput('TEST SCAN IMAGE COMMAND ENDED AT: ' . Carbon::now()->format('H:i:s'));
    }

    /**
     * @return mixed
     * @deprecated
     *
     */
    private function getSchema(): mixed
    {
        $contents = file_get_contents(storage_path('app/public/scanner/configs/template_page_1.svg'));

        $document = new DOMDocument();
        $document->loadXML($contents);

        $schema = [
            'area_adjustment_percentage' => env('SCANNER_AREA_ADJUSTMENT_PERCENTAGE', 90),
            'tolerance'                  => env('SCANNER_CHECKBOX_TOLERANCE', 60),
        ];

        $svg              = $document->getElementsByTagName('svg');
        $schema['width']  = str_replace('mm', '', ($svg[0]->getAttribute('width') ?? 0));
        $schema['height'] = str_replace('mm', '', ($svg[0]->getAttribute('height') ?? 0));

        $lines = $document->getElementsByTagName('line');
        foreach ($lines as $line) {
            if ($line->getAttribute('data-type') === 'mark') {
                $label = $line->getAttribute('data-label');
                switch ($label) {
                    case 'tl':
                        $schema['markers']['top']['left'] = [
                            'x' => str_replace('mm', '', $line->getAttribute('x1')),
                            'y' => str_replace('mm', '', $line->getAttribute('y1')),
                        ];
                        break;
                    case 'tr':
                        $schema['markers']['top']['right'] = [
                            'x' => str_replace('mm', '', $line->getAttribute('x2')),
                            'y' => str_replace('mm', '', $line->getAttribute('y2')),
                        ];
                        break;
                    case 'bl':
                        $schema['markers']['bottom']['left'] = [
                            'x' => str_replace('mm', '', $line->getAttribute('x1')),
                            'y' => str_replace('mm', '', $line->getAttribute('y1')),
                        ];
                        break;
                    case 'br':
                        $schema['markers']['bottom']['right'] = [
                            'x' => str_replace('mm', '', $line->getAttribute('x2')),
                            'y' => str_replace('mm', '', $line->getAttribute('y2')),
                        ];
                        break;
                }
            }
        }

        $labels = $document->getElementsByTagName('rect');
        foreach ($labels as $label) {
            if (!empty($label->getAttribute('data-type'))) {
                $schema['labels'][] = [
                    'x'           => round(str_replace('mm', '', $label->getAttribute('x')), 2),
                    'y'           => round(str_replace('mm', '', $label->getAttribute('y')), 2),
                    'width'       => round(str_replace('mm', '', $label->getAttribute('width')), 2),
                    'height'      => round(str_replace('mm', '', $label->getAttribute('height')), 2),
                    'question_id' => $label->getAttribute('data-question_id'),
                    'type'        => $label->getAttribute('data-type'),
                    'name'        => $label->getAttribute('data-label'),
                ];
            }
        }

        return json_decode(json_encode($schema));
    }

    /**
     * @param string $directory
     */
    private function initializeDirectories(string $directory)
    {
        if (!File::isDirectory(storage_path($this->path))) {
            File::makeDirectory(storage_path($this->path), 0775, true);
        }

        if (!File::isDirectory(storage_path($this->resultsPath))) {
            File::makeDirectory(storage_path($this->resultsPath), 0775, true);
        }

        if (!File::isDirectory(storage_path($this->resultsPath . '/' . $directory))) {
            File::makeDirectory(storage_path($this->resultsPath . '/' . $directory), 0775, true);
        }

        $this->initLog($directory);
    }

    /**
     * @param int $dpi
     *
     * @return string
     * @deprecated
     *
     */
    private function getImagePath(int $dpi)
    {
        return storage_path('app/public/examples/p_poc_' . $dpi . '_dpi.jpg');
    }

    /**
     * @param int $dpi
     *
     * @return string
     * @deprecated
     *
     */
    private function getDebugPath(int $dpi)
    {
        return storage_path('app/public/examples/results/' . $dpi);
    }

    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getConfigPath(int $dpi = 300): string
    {
        return storage_path('app/public/scanner/configs/config_' . $dpi . '_dpi.json');
    }

    /**
     * @return string
     * @deprecated
     *
     */
    private function getSchemaPath()
    {
        return storage_path('app/public/scanner/configs/schema.json');
    }

    /**
     * @param string $directory
     */
    private function initLog(string $directory)
    {
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => storage_path($this->resultsPath . '/' . $directory . '/scan.log'),
                'level'  => env('SCANNER_LOG_MODE', 'debug'),
            ]
        );
    }
}
