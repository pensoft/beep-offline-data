<?php

namespace App\Console\Commands\Tests\Ivo;

use App\Console\PensoftOmrCommands;
use App\Vendors\ScannerTest\ImagickScanner;
use App\Vendors\ScannerTest\Map\Map;
use Carbon\Carbon;
use Exception;
use Imagick;
use ImagickPixel;

class ScanImageCron extends PensoftOmrCommands
{
    private string $path = 'app/public/tests/ivo/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tests:ivo:scan-image {--dpi=}';

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
            $dpi = !empty($this->option('dpi')) ? $this->option('dpi') : 300;

            $scanner = new ImagickScanner();
            $this->writeOutput('Scanner initialized');
            $scanner->setDebug(true);
            $this->writeOutput('Debug initialized');
            $scanner->setImagePath($this->getImagePath($dpi));
            $this->writeOutput('Document: ' . $this->getImagePath($dpi));
            $scanner->setDebugPath($this->getDebugImagePath($dpi));
            $this->writeOutput('Debug document: ' . $this->getDebugImagePath($dpi));

            $map = Map::create($this->getMapPath($dpi));
            $this->writeOutput('Map: ' . $this->getMapPath($dpi));

            $result = $scanner->scan($map);

            $this->writeOutput(json_encode($result->toArray()));

            $this->writeOutput(print_r($result->toArray(), true));
        } catch (Exception $exception) {
            $this->writeOutput('[ScanImageCron] Cron interrupted with exception', 'error');
            $this->writeOutput('Message: ' . $exception->getMessage(), 'error');
            $this->writeOutput('Line: ' . $exception->getLine(), 'error');
            $this->writeOutput('Code: ' . json_encode($exception->getCode()), 'error');
            $this->writeOutput(print_r($exception->getCode(), true), 'error');
        }

        $this->writeOutput('TEST SCAN IMAGE COMMAND ENDED AT: ' . Carbon::now()->format('H:i:s'));
    }



    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getImagePath(int $dpi)
    {
        return storage_path('app/public/examples/p_poc_' . $dpi . '_dpi.jpg');
    }

    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getDebugImagePath(int $dpi)
    {
        return str_replace('_dpi', '_dpi_debug', $this->getImagePath($dpi));
    }

    /**
     * @param int $dpi
     *
     * @return string
     */
    private function getMapPath(int $dpi)
    {
        return storage_path('app/public/examples/map_' . $dpi . '_dpi.json');
    }
}
