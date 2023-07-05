<?php

namespace App\Vendors\Scanner\Scanner;

use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Converter\Dpm;
use App\Vendors\Scanner\Label\CheckboxLabel;
use App\Vendors\Scanner\Label\NumericLabel;
use App\Vendors\Scanner\Label\TextLabel;
use App\Vendors\Scanner\Schema\Schema;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class Scanner
{
    /** @var \Imagick */
    private Imagick $image;

    /** @var \ImagickDraw */
    protected ImagickDraw $draw;

    /** @var \Psr\Log\LoggerInterface */
    protected \Psr\Log\LoggerInterface $log;

    /** @var \App\Vendors\Scanner\Schema\Schema */
    private Schema $schema;

    /** @var Config */
    private Config $config;

    /** @var Dpm */
    private Dpm $dpm;

    /** @var string */
    private string $folder;

    /** @var array */
    private array $scanResults = [];

    /**
     * ImagickScanner constructor.
     *
     * @param \Imagick                           $image
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     * @param \App\Vendors\Scanner\Config\Config $config
     * @param string                             $folder
     */
    public function __construct(Imagick $image, Schema $schema, Config $config, string $folder)
    {
        $this->setFolder($folder);
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => config('scanner.log_mode'),
            ]
        );

        $this->setImage($image);
        $this->setSchema($schema);
        $this->setConfig($config);

        $this->setDpm(new Dpm($schema, $config, $folder));
    }

    /**
     * @param array $externalScanResults
     *
     * @return array
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     * @throws \thiagoalessio\TesseractOCR\TesseractOcrException
     */
    public function scan(array $externalScanResults = []): array
    {
        foreach ($this->getSchema()->getLabels() as $labelData) {
            if ($labelData->type === 'text') {
                $label = new TextLabel(
                    $this->getImage(),
                    $this->getSchema(),
                    $this->getConfig(),
                    $this->getDpm(),
                    $labelData,
                    $this->getFolder()
                );
                $label->scan($this->getFolder(), $externalScanResults);

                $this->addScanResult($label->getResult());

                $label->markLabelImage();
            } elseif ($labelData->type === 'number' || $labelData->type === 'single-digit') {
                $label = new NumericLabel(
                    $this->getImage(),
                    $this->getSchema(),
                    $this->getConfig(),
                    $this->getDpm(),
                    $labelData,
                    $this->getFolder()
                );
                $label->scan($this->getFolder(), $externalScanResults);

                $this->addScanResult($label->getResult());

                $label->markLabelImage();
            } elseif ($labelData->type === 'checkbox') {
                $label = new CheckboxLabel(
                    $this->getImage(),
                    $this->getSchema(),
                    $this->getConfig(),
                    $this->getDpm(),
                    $labelData,
                    $this->getFolder()
                );
                $label->scan($this->getFolder());

                $this->addScanResult($label->getResult());

                $strokeColor = $label->isChecked() ? '#00CC00' : '#CC0000';
                $label->markLabelImage($strokeColor);
            } else {
                $result = [
                    'category_id'        => $labelData->category_id ?? '',
                    'parent_category_id' => $labelData->parent_category_id ?? '',
                    'type'               => $labelData->type ?? '',
                    'value'              => [],
                    'image'              => [],
                ];

                $this->addScanResult($result);
            }
        }

        $this->markMarks();

        $this->getImage()->writeImage($this->getFolder() . '/' . 'debug.jpg');

        return $this->getScanResults();
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    private function markMarks(): void
    {
        $this->setDraw();
        $this->getDraw()->setStrokeColor(new ImagickPixel('#00CC00'));

        $topLeft = $this->getConfig()->getTopLeft();
        $x       = $topLeft->getX();
        $y       = $topLeft->getY();
        $this->getDraw()->rectangle($x - 10, $y - 10, $x + 10, $y + 10);
        $this->getDraw()->circle($x, $y, $x + 2, $y);

        $topRight = $this->getConfig()->getTopRight();
        $x        = $topRight->getX();
        $y        = $topRight->getY();
        $this->getDraw()->rectangle($x - 10, $y - 10, $x + 10, $y + 10);
        $this->getDraw()->circle($x, $y, $x + 2, $y);

        $bottomLeft = $this->getConfig()->getBottomLeft();
        $x          = $bottomLeft->getX();
        $y          = $bottomLeft->getY();
        $this->getDraw()->rectangle($x - 10, $y - 10, $x + 10, $y + 10);
        $this->getDraw()->circle($x, $y, $x + 2, $y);

        $bottomRight = $this->getConfig()->getBottomRight();
        $x           = $bottomRight->getX();
        $y           = $bottomRight->getY();
        $this->getDraw()->rectangle($x - 10, $y - 10, $x + 10, $y + 10);
        $this->getDraw()->circle($x, $y, $x + 2, $y);

        $this->getImage()->drawImage($this->getDraw());
    }

    /**
     * Set Imagick Draw
     *
     * @throws \ImagickDrawException
     */
    public function setDraw(): void
    {
        $this->draw = new ImagickDraw();
        $this->draw->setFontSize(6);
        $this->draw->setFillOpacity(0.4);
        $this->draw->setStrokeWidth(1);
        $this->draw->setStrokeOpacity(1);
        $this->draw->setFillOpacity(1);
        $this->draw->setFillColor(new ImagickPixel('#00000000'));
    }

    /**
     * @return \ImagickDraw
     */
    public function getDraw(): ImagickDraw
    {
        return $this->draw;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }

    /**
     * @return \Imagick
     */
    public function getImage(): Imagick
    {
        return $this->image;
    }

    /**
     * @param \Imagick $image
     */
    public function setImage(Imagick $image): void
    {
        $this->image = $image;
    }

    /**
     * @return \App\Vendors\Scanner\Schema\Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     */
    public function setSchema(Schema $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return \App\Vendors\Scanner\Config\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param \App\Vendors\Scanner\Config\Config $config
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
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
     * @param array $scanResult
     *
     * @return array
     */
    public function addScanResult(array $scanResult): array
    {
        $appendResult = true;
        foreach ($this->scanResults as &$result) {
            if ($result['category_id'] == $scanResult['category_id'] &&
                $result['parent_category_id'] == $scanResult['parent_category_id']) {
                $appendResult = false;

                $result['value'][] = $scanResult['value'];
                $result['image'][] = $scanResult['image'];
                break;
            }
        }

        if ($appendResult) {
            $scanResult['value'] = [$scanResult['value']];
            $scanResult['image'] = [$scanResult['image']];

            $this->scanResults[] = $scanResult;
        }

        return $this->scanResults;
    }

    /**
     * @return \App\Vendors\Scanner\Converter\Dpm
     */
    public function getDpm(): Dpm
    {
        return $this->dpm;
    }

    /**
     * @param \App\Vendors\Scanner\Converter\Dpm $dpm
     */
    public function setDpm(Dpm $dpm): void
    {
        $this->dpm = $dpm;
    }
}
