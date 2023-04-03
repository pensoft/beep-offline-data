<?php

namespace App\Console\Commands\Tests\Ivo;

use App\Console\PensoftOmrCommands;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Imagick;
use ImagickPixel;
use ImagickDraw;

class ImageMarkRecognitionCron extends PensoftOmrCommands
{
    private int   $markWidth  = 8;
    private int   $markHeight = 8;
    private int   $pageWidth  = 210;
    private int   $pageHeight = 297;
    private float $dpmX;
    private float $dpmY;

    private ImagickDraw $draw;
    private ImagickDraw $regionDraw;

    private string $path = 'app/public/tests/ivo/';

    private Imagick $markerImage;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:ivo:image-mark-recognition {--name=}';

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
        $this->markerImage = new Imagick(storage_path($this->path . 'marker-200.jpg'));
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->writeOutput('TEST IMAGICK MARK RECOGNITION COMMAND STARTED AT: ' . Carbon::now()->format('H:i:s'));

        try {
            $filename = !empty($this->option('name')) ? $this->option('name') : 'scan';
            $filepath = storage_path($this->path . $filename . '.jpg');
            if (!File::exists($filepath)) {
                $this->writeOutput('FILE NOT FOUND');

                return;
            }

            if (!File::isDirectory(storage_path($this->path . '/' . $filename))) {
                File::makeDirectory(storage_path($this->path . '/' . $filename), 0775, true);
            }
            $this->path .= '/' . $filename . '/';

            $this->setDraw();

            $image = new Imagick($filepath);
            $this->setDpmX($image->getImageWidth() / $this->pageWidth);
            $this->setDpmY($image->getImageHeight() / $this->pageHeight);
            $this->writeOutput('DPM: [' . $this->getDpmX() . 'x' . $this->getDpmY() . ']');
            $this->resizeMarker();

            $this->markTopLeftCorner($image);
            $this->printTopRightCorner($image);
            $this->printBottomLeftCorner($image);
            $this->printBottomRightCorner($image);

            $image->drawImage($this->getDraw());
            $image->writeImage(storage_path($this->path . '/debug.jpg'));
        } catch (Exception $exception) {
            $this->writeOutput('[ImageMarkRecognitionCron] Cron interrupted with exception', 'error');
            $this->writeOutput('Message: ' . $exception->getMessage(), 'error');
            $this->writeOutput('Line: ' . $exception->getLine(), 'error');
            $this->writeOutput('Code: ' . json_encode($exception->getCode()), 'error');
            $this->writeOutput(print_r($exception->getCode(), true), 'error');
        }

        $this->writeOutput('TEST IMAGICK MARK RECOGNITION COMMAND ENDED AT: ' . Carbon::now()->format('H:i:s'));
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    private function markTopLeftCorner(Imagick $image)
    {
        //        $this->writeOutput('Applying blackThresholdImage');
        //        $image->blackThresholdImage('grey');
        //        $this->writeOutput('Applying whiteThresholdImage');
        //        $image->whiteThresholdImage('grey');

        $startX = $startY = 0;
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.1;

        //        $this->writeOutput(
        //            'TL Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        //        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        //        $this->writeOutput('Storing TL image');
        $region->writeImage(storage_path($this->path . 'region_tl.jpg'));

        $offset     = [];
        $similarity = 0;
        $this->writeOutput('Searching TL mark');
        $recognition = $region->subImageMatch($this->markerImage, $offset, $similarity);
        $this->writeOutput("TL Offset: " . json_encode($offset));
        $this->writeOutput("TL Similarity: " . json_encode($similarity));
        $recognition->writeImage(storage_path($this->path . 'mark_recognition_tl.jpg'));

        $xTL = $yTL = 0;
        if (!empty($offset)) {
            $xTL = round($startX + $offset['x']);
            $yTL = round($startY + $offset['y']);
        }

        $this->setRegionDraw();
        $this->getRegionDraw()->rectangle($offset['x'] - 10, $offset['y'] - 10, $offset['x'] + 10, $offset['y'] + 10);
        $this->getRegionDraw()->circle($offset['x'], $offset['y'], $offset['x'] + 2, $offset['y']);
        $region->drawImage($this->getRegionDraw());
        $region->writeImage(storage_path($this->path . 'region_tl_drawn.jpg'));

        $this->getDraw()->rectangle($xTL - 10, $yTL - 10, $xTL + 10, $yTL + 10);
        $this->getDraw()->circle($xTL, $yTL, $xTL + 2, $yTL);

        $this->writeOutput('TOP LEFT: [' . $xTL . 'x' . $yTL . ']');
        $this->writeOutput('----------------------------');
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    private function printTopRightCorner(Imagick $image)
    {
        $startY = 0;
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.1;
        $startX = $image->getImageWidth() - $width;

        //        $this->writeOutput(
        //            'TR Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        //        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        //        $this->writeOutput('Storing TR image');
        $region->writeImage(storage_path($this->path . 'region_tr.jpg'));

        $offset     = [];
        $similarity = null;
        $this->writeOutput('Searching TR mark');
        $recognition = $region->subImageMatch($this->markerImage, $offset, $similarity);
        $this->writeOutput('TR Offset: ' . json_encode($offset));
        $this->writeOutput('TR Similarity: ' . json_encode($similarity));
        $recognition->writeImage(storage_path($this->path . 'mark_recognition_tr.jpg'));

        $xTR = $yTR = 0;
        if (!empty($offset)) {
            $xTR = round($startX + $offset['x']);
            $yTR = round($startY + $offset['y']);
        }

        $this->setRegionDraw();
        $this->getRegionDraw()->rectangle($offset['x'] - 10, $offset['y'] - 10, $offset['x'] + 10, $offset['y'] + 10);
        $this->getRegionDraw()->circle($offset['x'], $offset['y'], $offset['x'] + 2, $offset['y']);
        $region->drawImage($this->getRegionDraw());
        $region->writeImage(storage_path($this->path . 'region_tr_drawn.jpg'));

        $this->getDraw()->rectangle($xTR - 10, $yTR - 10, $xTR + 10, $yTR + 10);
        $this->getDraw()->circle($xTR, $yTR, $xTR + 2, $yTR);

        $this->writeOutput('TOP RIGHT: [' . $xTR . 'x' . $yTR . ']');
        $this->writeOutput('----------------------------');
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    private function printBottomLeftCorner(Imagick $image)
    {
        $startX = 0;
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.1;
        $startY = $image->getImageHeight() - $height;

        //        $this->writeOutput(
        //            'BL Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        //        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        //        $this->writeOutput('Storing BL image');
        $region->writeImage(storage_path($this->path . 'region_bl.jpg'));

        $offset     = [];
        $similarity = null;
        $this->writeOutput('Searching BL mark');
        $recognition = $region->subImageMatch($this->markerImage, $offset, $similarity);
        $this->writeOutput('BL Offset: ' . json_encode($offset));
        $this->writeOutput('BL Similarity: ' . json_encode($similarity));
        $recognition->writeImage(storage_path($this->path . 'mark_recognition_bl.jpg'));

        $xBL = $yBL = 0;
        if (!empty($offset)) {
            $xBL = round($startX + $offset['x']);
            $yBL = round($startY + $offset['y']);
        }

        $this->setRegionDraw();
        $this->getRegionDraw()->rectangle($offset['x'] - 10, $offset['y'] - 10, $offset['x'] + 10, $offset['y'] + 10);
        $this->getRegionDraw()->circle($offset['x'], $offset['y'], $offset['x'] + 2, $offset['y']);
        $region->drawImage($this->getRegionDraw());
        $region->writeImage(storage_path($this->path . 'region_bl_drawn.jpg'));

        $this->getDraw()->rectangle($xBL - 10, $yBL - 10, $xBL + 10, $yBL + 10);
        $this->getDraw()->circle($xBL, $yBL, $xBL + 2, $yBL);

        $this->writeOutput('BOTTOM LEFT: [' . $xBL . 'x' . $yBL . ']');
        $this->writeOutput('----------------------------');
    }

    /**
     * @param \Imagick $image
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    private function printBottomRightCorner(Imagick $image)
    {
        $width  = $image->getImageWidth() * 0.15;
        $height = $image->getImageHeight() * 0.1;
        $startX = $image->getImageWidth() - $width;
        $startY = $image->getImageHeight() - $height;

        //        $this->writeOutput(
        //            'BR Area analyze: [' . $startX . 'x' . $startY . '][' . ($startX + $width) . 'x' . ($startY + $height) . ']'
        //        );

        $region = $image->getImageRegion($width, $height, $startX, $startY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        //        $this->writeOutput('Storing BR image');
        $region->writeImage(storage_path($this->path . 'region_br.jpg'));

        $offset     = [];
        $similarity = null;
        $this->writeOutput('Searching BR mark');
        $recognition = $region->subImageMatch($this->markerImage, $offset, $similarity);
        $this->writeOutput('BR Offset: ' . json_encode($offset));
        $this->writeOutput('BR Similarity: ' . json_encode($similarity));
        $recognition->writeImage(storage_path($this->path . 'mark_recognition_br.jpg'));

        $xBR = $yBR = 0;
        if (!empty($offset)) {
            $xBR = round($startX + $offset['x']);
            $yBR = round($startY + $offset['y']);
        }

        $this->setRegionDraw();
        $this->getRegionDraw()->rectangle($offset['x'] - 10, $offset['y'] - 10, $offset['x'] + 10, $offset['y'] + 10);
        $this->getRegionDraw()->circle($offset['x'], $offset['y'], $offset['x'] + 2, $offset['y']);
        $region->drawImage($this->getRegionDraw());
        $region->writeImage(storage_path($this->path . 'region_br_drawn.jpg'));

        $this->getDraw()->rectangle($xBR - 10, $yBR - 10, $xBR + 10, $yBR + 10);
        $this->getDraw()->circle($xBR, $yBR, $xBR + 2, $yBR);

        $this->writeOutput('BOTTOM RIGHT: [' . $xBR . 'x' . $yBR . ']');
        $this->writeOutput('----------------------------');
    }

    /**
     * @param float $dpm
     */
    private function setDpmX(float $dpm)
    {
        $this->dpmX = $dpm;
    }

    /**
     * @return float
     */
    private function getDpmX(): float
    {
        return $this->dpmX;
    }

    /**
     * @param float $dpm
     */
    private function setDpmY(float $dpm)
    {
        $this->dpmY = $dpm;
    }

    /**
     * @return float
     */
    private function getDpmY(): float
    {
        return $this->dpmY;
    }

    /**
     * @throws \ImagickException
     */
    private function resizeMarker()
    {
        $width  = round($this->getDpmX() * $this->markWidth * 0.9);
        $height = round($this->getDpmY() * $this->markHeight * 0.9);

        $this->writeOutput('Resizing marker to: [' . $width . 'x' . $height . ']');
        $this->markerImage->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 0.9);
        $this->markerImage->setImagePage($width, $height, 0, 0);
        $this->markerImage->writeImage(storage_path($this->path . 'mark_resized.jpg'));
    }

    /**
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
        $this->draw->setStrokeColor(new ImagickPixel('#00CC00'));
    }

    /**
     * @return \ImagickDraw
     */
    public function getDraw(): ImagickDraw
    {
        return $this->draw;
    }

    /**
     * @throws \ImagickDrawException
     */
    public function setRegionDraw(): void
    {
        $this->regionDraw = new ImagickDraw();
        $this->regionDraw->setFontSize(6);
        $this->regionDraw->setFillOpacity(0.4);
        $this->regionDraw->setStrokeWidth(1);
        $this->regionDraw->setStrokeOpacity(1);
        $this->regionDraw->setFillOpacity(1);
        $this->regionDraw->setFillColor(new ImagickPixel('#00000000'));
        $this->regionDraw->setStrokeColor(new ImagickPixel('#00CC00'));
    }

    /**
     * @return \ImagickDraw
     */
    public function getRegionDraw(): ImagickDraw
    {
        return $this->regionDraw;
    }
}
