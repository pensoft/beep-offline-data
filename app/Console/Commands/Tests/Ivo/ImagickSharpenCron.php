<?php

namespace App\Console\Commands\Tests\Ivo;

use App\Console\PensoftOmrCommands;
use Carbon\Carbon;
use Exception;
use Imagick;
use ImagickPixel;

class ImagickSharpenCron extends PensoftOmrCommands
{
    private string $path = 'app/public/tests/ivo/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:ivo:image-sharpen';

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
        $this->writeOutput('TEST IMAGICK SHARPEN COMMAND STARTED AT: ' . Carbon::now()->format('H:i:s'));

        try {
            $image = new Imagick(storage_path($this->path . 'p_poc_600_dpi.jpg'));
            $image->blackThresholdImage('grey');
            $image->whiteThresholdImage('grey');
//            $this->sharpenImage($image, 0, 10);
//            $this->sharpenImage($image, 0, 25);
//            $this->sharpenImage($image, 0, 50);
            $image->writeImage(storage_path($this->path . 'p_poc_600_dpi_threshold.jpg'));
        } catch (Exception $exception) {
            $this->writeOutput('[ImagickSharpenCron] Cron interrupted with exception', 'error');
            $this->writeOutput('Message: ' . $exception->getMessage(), 'error');
            $this->writeOutput('Line: ' . $exception->getLine(), 'error');
            $this->writeOutput('Code: ' . json_encode($exception->getCode()), 'error');
            $this->writeOutput(print_r($exception->getCode(), true), 'error');
        }

        $this->writeOutput('TEST IMAGICK SHARPEN COMMAND ENDED AT: ' . Carbon::now()->format('H:i:s'));
    }

    private function sharpenImage(Imagick $image, float $radius, float $sigma)
    {
        $temImage = clone $image;
        $temImage->sharpenImage($radius, $sigma);
        $filename = 'sharpen_result_r-' . $radius .'_s-' . $sigma . '.jpg';
        $temImage->writeImage(storage_path($this->path . $filename));

//
//        $temImage = clone $image;
//        $temImage->sharpenImage($radius, $sigma, Imagick::CHANNEL_GRAY_CHANNELS);
//        $filename = 'sharpen_result_r-' . $radius .'_s-' . $sigma . '_grey_channels.jpg';
//        $temImage->writeImage(storage_path($this->path . $filename));
    }
}
