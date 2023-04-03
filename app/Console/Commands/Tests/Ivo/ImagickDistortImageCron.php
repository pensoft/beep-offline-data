<?php

namespace App\Console\Commands\Tests\Ivo;

use App\Console\PensoftOmrCommands;
use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Marker\MarkRecognition;
use App\Vendors\Scanner\Schema\Schema;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Imagick;
use Request;

class ImagickDistortImageCron extends PensoftOmrCommands
{
    private string $path        = 'app/public/scans';
    private string $resultsPath = 'app/public/scans/distortions';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:ivo:image-distort {--file=} {--page=}';

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
        $this->writeOutput('TEST IMAGICK DISTORT COMMAND STARTED AT: ' . Carbon::now()->format('H:i:s'));

        try {
            $svgContents = file_get_contents(storage_path('app/public/scanner/configs/test_4_markers_v5.svg'));

            $filename = !empty($this->option('file')) ? $this->option('file') : 'scan';
            $filepath = storage_path($this->path . '/' . $filename . '.jpg');
            if (!File::exists($filepath)) {
                $this->writeOutput('FILE NOT FOUND');

                return;
            }

            $arguments = [];
            $directory = str_replace('.', '-', Request::ip()) . '_' . Carbon::now()->format('Ymd-His');
            $this->writeOutput('Directory Path: ' . $directory);
            $this->initializeDirectories($directory);
            $this->resultsPath .= '/' . $directory;

            $this->writeOutput('Filepath: ' . $filepath);
            $this->writeOutput('Destination Filepath: ' .
                               storage_path($this->resultsPath . '/' . pathinfo($filepath, PATHINFO_BASENAME)));
            File::copy($filepath, storage_path($this->resultsPath . '/' . pathinfo($filepath, PATHINFO_BASENAME)));
            $finalFilepath = storage_path($this->resultsPath . '/' . pathinfo($filepath, PATHINFO_FILENAME) .
                                          '_distorted.' . pathinfo($filepath, PATHINFO_EXTENSION));
            File::copy($filepath, $finalFilepath);

            $image = new Imagick($finalFilepath);

            $page       = !empty($this->option('page')) ? (int)$this->option('page') : 1;
            $schemaData = Schema::read($svgContents, ['eng'], $page);
            $schema     = new Schema($schemaData, storage_path($this->resultsPath));
            $this->writeLog('PAGE: ' . $page);

            $markRecognition = new MarkRecognition($image, $schema, storage_path($this->resultsPath));
            $markRecognition->recognizeMarkers(false);
            $markers = $markRecognition->getMarkers();

            $configData = json_decode(json_encode($markers));
            $config     = new Config($configData, storage_path($this->resultsPath));

            $this->writeLog('Top width: ' . $config->getTopWidth());
            $this->writeLog('Bottom width: ' . $config->getBottomWidth());
            $this->writeLog('AVG width: ' . $config->getWidth());
            $this->writeLog('Left height: ' . $config->getLeftHeight());
            $this->writeLog('Right height: ' . $config->getRightHeight());
            $this->writeLog('AVG height: ' . $config->getHeight());
            $this->writeLog('__________________________');

            $arguments[] = $config->getTopLeft()->getX();
            $arguments[] = $config->getTopLeft()->getY();
            $arguments[] = $config->getTopLeft()->getX();
            $arguments[] = $config->getTopLeft()->getY();

            $arguments[] = $config->getTopRight()->getX();
            $arguments[] = $config->getTopRight()->getY();
            $arguments[] = $config->getTopLeft()->getX() + $config->getWidth();
            $arguments[] = $config->getTopLeft()->getY();

            $arguments[] = $config->getBottomLeft()->getX();
            $arguments[] = $config->getBottomLeft()->getY();
            $arguments[] = $config->getTopLeft()->getX();
            $arguments[] = $config->getTopLeft()->getY() + $config->getHeight();

            $arguments[] = $config->getBottomRight()->getX();
            $arguments[] = $config->getBottomRight()->getY();
            $arguments[] = $config->getTopLeft()->getX() + $config->getWidth();
            $arguments[] = $config->getTopLeft()->getY() + $config->getHeight();


            //            $this->writeLog('Working on TOP LEFT marker');
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getTopLeft()->getX() . 'x' . $config->getTopLeft()->getY() . ']'
            //            );
            //            $this->writeLog('__________________________');

            /*
            $this->writeLog('Working on TOP LEFT marker');
            $movementX   = $this->getMovement($config->getTopWidth(), $config->getWidth());
            $movementY   = $this->getMovement($config->getLeftHeight(), $config->getHeight());
            $arguments[] = $config->getTopLeft()->getX();
            $arguments[] = $config->getTopLeft()->getY();

            $this->writeLog(
                'Coordinates: [' . $config->getTopLeft()->getX() . 'x' . $config->getTopLeft()->getY() . ']'
            );

            $config->getTopLeft()->move($movementX, $movementY);

            $this->writeLog('Movement: [' . ($movementX) . 'x' . ($movementY) . ']');
            $this->writeLog(
                'Coordinates: [' . $config->getTopLeft()->getX() . 'x' . $config->getTopLeft()->getY() . ']'
            );

            $arguments[] = $config->getTopLeft()->getX();
            $arguments[] = $config->getTopLeft()->getY();
            $this->writeLog('__________________________');
            */

            //            $this->writeLog('Working on TOP RIGHT marker');
            //            $arguments[] = $config->getTopRight()->getX();
            //            $arguments[] = $config->getTopRight()->getY();
            //
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getTopRight()->getX() . 'x' . $config->getTopRight()->getY() . ']'
            //            );
            //            $movementX = $config->getTopRight()->getX() - $config->getWidth() - $config->getTopLeft()->getX();
            //            $movementY = $config->getTopRight()->getY() - $config->getTopLeft()->getY();
            //
            //            $config->getTopRight()->move(-$movementX, -$movementY);
            //
            //            $this->writeLog('Movement: [' . (-$movementX) . 'x' . (-$movementY) . ']');
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getTopRight()->getX() . 'x' . $config->getTopRight()->getY() . ']'
            //            );
            //
            //            $arguments[] = $config->getTopRight()->getX();
            //            $arguments[] = $config->getTopRight()->getY();
            //            $this->writeLog('__________________________');

            /*
            $this->writeLog('Working on TOP RIGHT marker');
            $movementY   = $this->getMovement($config->getRightHeight(), $config->getHeight());
            $arguments[] = $config->getTopRight()->getX();
            $arguments[] = $config->getTopRight()->getY();

            $this->writeLog(
                'Coordinates: [' . $config->getTopRight()->getX() . 'x' . $config->getTopRight()->getY() . ']'
            );

            $config->getTopRight()->move(-$movementX, $movementY);

            $this->writeLog('Movement: [' . (-$movementX) . 'x' . ($movementY) . ']');
            $this->writeLog(
                'Coordinates: [' . $config->getTopRight()->getX() . 'x' . $config->getTopRight()->getY() . ']'
            );

            $arguments[] = $config->getTopRight()->getX();
            $arguments[] = $config->getTopRight()->getY();
            $this->writeLog('__________________________');
            */

            //            $this->writeLog('Working on BOTTOM LEFT marker');
            //            $arguments[] = $config->getBottomLeft()->getX();
            //            $arguments[] = $config->getBottomLeft()->getY();
            //
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getBottomLeft()->getX() . 'x' . $config->getBottomLeft()->getY() . ']'
            //            );
            //
            //            $movementX = $config->getBottomLeft()->getX() - $config->getTopLeft()->getX();
            //            $movementY = $config->getBottomLeft()->getY() - $config->getHeight() - $config->getTopLeft()->getY();
            //
            //            $config->getBottomLeft()->move(-$movementX, -$movementY);
            //
            //            $this->writeLog('Movement: [' . (-$movementX) . 'x' . (-$movementY) . ']');
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getBottomLeft()->getX() . 'x' . $config->getBottomLeft()->getY() . ']'
            //            );
            //
            //            $arguments[] = $config->getBottomLeft()->getX();
            //            $arguments[] = $config->getBottomLeft()->getY();
            //            $this->writeLog('__________________________');

            /*
            $this->writeLog('Working on BOTTOM LEFT marker');
            $movementX   = $this->getMovement($config->getBottomWidth(), $config->getWidth());
            $movementY   = $this->getMovement($config->getLeftHeight(), $config->getHeight());
            $arguments[] = $config->getBottomLeft()->getX();
            $arguments[] = $config->getBottomLeft()->getY();

            $this->writeLog(
                'Coordinates: [' . $config->getBottomLeft()->getX() . 'x' . $config->getBottomLeft()->getY() . ']'
            );

            $config->getBottomLeft()->move($movementX, -$movementY);

            $this->writeLog('Movement: [' . ($movementX) . 'x' . (-$movementY) . ']');
            $this->writeLog(
                'Coordinates: [' . $config->getBottomLeft()->getX() . 'x' . $config->getBottomLeft()->getY() . ']'
            );

            $arguments[] = $config->getBottomLeft()->getX();
            $arguments[] = $config->getBottomLeft()->getY();
            $this->writeLog('__________________________');
            */

            //            $this->writeLog('Working on BOTTOM RIGHT marker');
            //            $arguments[] = $config->getBottomRight()->getX();
            //            $arguments[] = $config->getBottomRight()->getY();
            //
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getBottomRight()->getX() . 'x' . $config->getBottomRight()->getY() . ']'
            //            );
            //
            //            $movementX = $config->getBottomRight()->getX() - $config->getWidth() - $config->getTopLeft()->getX();
            //            $movementY = $config->getBottomRight()->getY() - $config->getHeight() - $config->getTopLeft()->getY();
            //
            //            $config->getBottomRight()->move(-$movementX, -$movementY);
            //
            //            $this->writeLog('Movement: [' . (-$movementX) . 'x' . (-$movementY) . ']');
            //            $this->writeLog(
            //                'Coordinates: [' . $config->getBottomRight()->getX() . 'x' . $config->getBottomRight()->getY() . ']'
            //            );
            //
            //            $arguments[] = $config->getBottomRight()->getX();
            //            $arguments[] = $config->getBottomRight()->getY();
            //            $this->writeLog('__________________________');

            /*
            $this->writeLog('Working on BOTTOM RIGHT marker');
            $movementY   = $this->getMovement($config->getRightHeight(), $config->getHeight());
            $arguments[] = $config->getBottomRight()->getX();
            $arguments[] = $config->getBottomRight()->getY();

            $this->writeLog(
                'Coordinates: [' . $config->getBottomRight()->getX() . 'x' . $config->getBottomRight()->getY() . ']'
            );

            $config->getBottomRight()->move(-$movementX, -$movementY);

            $this->writeLog('Movement: [' . (-$movementX) . 'x' . (-$movementY) . ']');
            $this->writeLog(
                'Coordinates: [' . $config->getBottomRight()->getX() . 'x' . $config->getBottomRight()->getY() . ']'
            );

            $arguments[] = $config->getBottomRight()->getX();
            $arguments[] = $config->getBottomRight()->getY();
            $this->writeLog('__________________________');
            */

            $this->writeOutput('ARGUMENTS: ' . json_encode($arguments));

            $image->distortImage(Imagick::DISTORTION_PERSPECTIVE, $arguments, true);

            $this->writeLog('Path: ' . $finalFilepath);
            $image->writeImage($finalFilepath);
        } catch (Exception $exception) {
            $this->writeOutput('[ImagickDistortImageCron] Cron interrupted with exception', 'error');
            $this->writeOutput('Message: ' . $exception->getMessage(), 'error');
            $this->writeOutput('Line: ' . $exception->getLine(), 'error');
            $this->writeOutput('Code: ' . json_encode($exception->getCode()), 'error');
            $this->writeOutput(print_r($exception->getCode(), true), 'error');
        }

        $this->writeOutput('TEST IMAGICK DISTORT COMMAND ENDED AT: ' . Carbon::now()->format('H:i:s'));
    }

    /**
     * @param $length
     * @param $avgLength
     *
     * @return int
     */
    private function getMovement($length, $avgLength)
    {
        $offset = $length - $avgLength;

        return (int)($offset / 2);
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
