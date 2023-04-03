<?php

namespace App\Console\Commands\Tests\Ivo;

use App\Console\PensoftOmrCommands;
use Carbon\Carbon;
use Exception;
use Imagick;
use ImagickPixel;

class ImageCornersCron extends PensoftOmrCommands
{
    private string $path = 'app/public/tests/ivo/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:ivo:image-corners';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Running tests for PHP Imagick library';

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
        $this->writeOutput('TEST IMAGICK CORNERS COMMAND STARTED AT: ' . Carbon::now()->format('H:i:s'));

        try {
            $image = new Imagick(storage_path($this->path . 'p_poc_600_dpi.jpg'));
            $this->writeOutput('Applying blackThresholdImage');
            $image->blackThresholdImage('grey');
            $this->writeOutput('Applying whiteThresholdImage');
            $image->whiteThresholdImage('grey');

            $this->printTopLeftCorner($image);
            $this->printTopRightCorner($image);
            $this->printBottomLeftCorner($image);
            $this->printBottomRightCorner($image);

            //            asort($data);
            //            ksort($data);

        } catch (Exception $exception) {
            $this->writeOutput('[ImageCornersCron] Cron interrupted with exception', 'error');
            $this->writeOutput('Message: ' . $exception->getMessage(), 'error');
            $this->writeOutput('Line: ' . $exception->getLine(), 'error');
            $this->writeOutput('Code: ' . json_encode($exception->getCode()), 'error');
            $this->writeOutput(print_r($exception->getCode(), true), 'error');
        }

        $this->writeOutput('TEST IMAGICK CORNERS COMMAND ENDED AT: ' . Carbon::now()->format('H:i:s'));
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickException
     * @throws \ImagickPixelIteratorException
     */
    private function printTopLeftCorner(Imagick $image)
    {
        $x = $image->getImageWidth();
        $y = $image->getImageHeight();

        $startX = $startY = 0;
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.15;

        $this->writeOutput(
            'TL Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        $this->writeOutput('Sharpening TL image');
        $region->sharpenImage(0, 25);
        $this->writeOutput('Storing TL image');
        $region->writeImage(storage_path($this->path . 'p_poc_600_dpi_region_tl.jpg'));

        $pixels = $region->getPixelRegionIterator(0, 0, $region->getImageWidth(), $region->getImageHeight());

        foreach ($pixels as $row => $rowPixels) {
            foreach ($rowPixels as $column => $pixel) {
                $hsl = $pixel->getHSL();
                if ($hsl['luminosity'] < 0.1) {
                    if ($row < $x && $column < $y) {
                        $x = $row;
                        $y = $column;
                        $this->writeOutput(
                            '[' . $x . 'x' . $y . '] Color: ' . $pixel->getColorAsString() .
                            ' => Luminosity: ' . $hsl['luminosity']
                        );
                    }
                }
            }
        }

        $this->writeOutput('Top left: [' . $x . 'x' . $y . ']');
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickException
     * @throws \ImagickPixelIteratorException
     */
    private function printTopRightCorner(Imagick $image)
    {
        $x = 0;
        $y = $image->getImageHeight();

        $startY = 0;
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.15;
        $startX = $image->getImageWidth() - $width;

        $this->writeOutput(
            'TR Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        $this->writeOutput('Sharpening TR image');
        $region->sharpenImage(0, 25);
        $this->writeOutput('Storing TR image');
        $region->writeImage(storage_path($this->path . 'p_poc_600_dpi_region_tr.jpg'));

        $pixels = $region->getPixelRegionIterator(0, 0, $region->getImageWidth(), $region->getImageHeight());

        foreach ($pixels as $row => $rowPixels) {
            foreach ($rowPixels as $column => $pixel) {
                $hsl = $pixel->getHSL();
                if ($hsl['luminosity'] < 0.5) {
                    if ($row > $x) {
                        $x = $row;
                    }
                    if ($column < $y) {
                        $y = $column;
                    }
                }
            }
        }

        $this->writeOutput('Top right: [' . $x . 'x' . $y . ']');
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickException
     * @throws \ImagickPixelIteratorException
     */
    private function printBottomLeftCorner(Imagick $image)
    {
        $x = $image->getImageWidth();
        $y = 0;

        $startX = 0;
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.15;
        $startY = $image->getImageHeight() - $height;

        $this->writeOutput(
            'BL Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        $this->writeOutput('Sharpening BL image');
        $region->sharpenImage(0, 25);
        $this->writeOutput('Storing BL image');
        $region->writeImage(storage_path($this->path . 'p_poc_600_dpi_region_bl.jpg'));

        $pixels = $region->getPixelRegionIterator(0, 0, $region->getImageWidth(), $region->getImageHeight());

        foreach ($pixels as $row => $rowPixels) {
            foreach ($rowPixels as $column => $pixel) {
                $hsl = $pixel->getHSL();
                if ($hsl['luminosity'] < 0.5) {
                    if ($row < $x) {
                        $x = $row;
                    }
                    if ($column > $y) {
                        $y = $column;
                    }
                }
            }
        }

        $this->writeOutput('Bottom left: [' . $x . 'x' . $y . ']');
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickException
     * @throws \ImagickPixelIteratorException
     */
    private function printBottomRightCorner(Imagick $image)
    {
        $x = 0;
        $y = 0;

        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.15;
        $startX = $image->getImageWidth() - $width;
        $startY = $image->getImageHeight() - $height;
        $pixels = $image->getPixelRegionIterator($startX, $startY, $width, $height);

        $this->writeOutput(
            'BR Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        $this->writeOutput('Sharpening BR image');
        $region->sharpenImage(0, 25);
        $this->writeOutput('Storing BR image');
        $region->writeImage(storage_path($this->path . 'p_poc_600_dpi_region_br.jpg'));

        $pixels = $region->getPixelRegionIterator(0, 0, $region->getImageWidth(), $region->getImageHeight());

        foreach ($pixels as $row => $rowPixels) {
            foreach ($rowPixels as $column => $pixel) {
                $hsl = $pixel->getHSL();
                if ($hsl['luminosity'] < 0.5) {
                    if ($row > $x) {
                        $x = $row;
                    }
                    if ($column > $y) {
                        $y = $column;
                    }
                }
            }
        }

        $this->writeOutput('Bottom Right: [' . $x . 'x' . $y . ']');
    }
}
