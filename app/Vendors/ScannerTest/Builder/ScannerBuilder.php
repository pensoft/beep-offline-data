<?php

namespace App\Vendors\ScannerTest\Builder;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Imagick;
use App\Vendors\ScannerTest\Map\Map;
use App\Vendors\ScannerTest\Result;
use App\Vendors\ScannerTest\Shape\Area;
use App\Vendors\ScannerTest\Shape\Point;
use App\Vendors\ScannerTest\Target\CircleTarget;
use App\Vendors\ScannerTest\Target\RectangleTarget;
use App\Vendors\ScannerTest\Target\TextTarget;

abstract class ScannerBuilder
{
    protected \Psr\Log\LoggerInterface $log;

    /**
     * Path image to be scanned
     *
     * @var string
     */
    protected string $imagePath;

    /**
     * Debug flag
     *
     * @var boolean
     */
    protected bool $debug = false;

    /**
     * Path image to wirte debug image file
     *
     * @var string
     */
    protected string $debugPath = 'debug.jpg';

    /**
     * Most point to the top/right
     *
     * @param \App\Vendors\ScannerTest\Shape\Point $near
     *
     * @return \App\Vendors\ScannerTest\Shape\Point
     */
    protected abstract function topRight(Point $near): Point;

    /**
     * Most point to the bottom/left
     *
     * @param \App\Vendors\ScannerTest\Shape\Point $near
     *
     * @return \App\Vendors\ScannerTest\Shape\Point
     */
    protected abstract function bottomLeft(Point $near): Point;

    /**
     * Returns pixel analysis in a rectangular area
     *
     * @param Point $a
     * @param Point $b
     * @param float $tolerance
     *
     * @return Area
     */
    protected abstract function rectangleArea(Point $a, Point $b, float $tolerance): Area;

    /**
     * Returns pixel analysis in a circular area
     *
     * @param Point $a
     * @param float $radius
     * @param float $tolerance
     *
     * @return Area
     */
    protected abstract function circleArea(Point $a, float $radius, float $tolerance): Area;

    /**
     * Returns image blob in a rectangular area
     *
     * @param Point $a
     * @param Point $b
     *
     * @return Imagick
     */
    protected abstract function textArea(Point $a, Point $b): Imagick;

    /**
     * Increases or decreases image size
     *
     * @param float $percent
     */
    protected abstract function adjustSize(float $percent): float;

    /**
     * Rotate image
     *
     * @param float $degrees
     */
    protected abstract function adjustRotate(float $degrees): float;

    /**
     * Generate file debug.jpg with targets, topRight and buttonLeft
     *
     * @param void
     */
    protected abstract function debug();

    /**
     * Finish processes
     *
     * @param void
     */
    protected abstract function finish();

    /**
     * Set image path
     *
     * @param mixed $imagePath
     */
    public function setImagePath(mixed $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * Set debug image path
     *
     * @param mixed $debugPath
     */
    public function setDebugPath(mixed $debugPath)
    {
        $this->debugPath = $debugPath;
    }

    /**
     * Scan specific image
     *
     * @param Map $map
     *
     * @return Result
     */
    public function scan(Map $map): Result
    {
        $this->log->debug('Scan started');
        $info = getimagesize($this->imagePath);

        /*
         * Setup result
         */
        $this->log->debug('Setting up Result');
        $result = new Result();
        $result->setDimensions($info[0], $info[1]);
        $result->setImageMime($info['mime']);
        $result->setImagePath($this->imagePath);

        /*
         * Check map
         */
        $this->log->debug('Checking Map');
        $topRightMap   = $map->topRight();
        $bottomLeftMap = $map->bottomLeft();

        $angleMap           = $this->anglePoints($topRightMap, $bottomLeftMap);
        $distanceCornersMap = $this->distancePoints($topRightMap, $bottomLeftMap);

        /*
         * Check image
         */
        $this->log->debug('Checking Image');
        $topRightImage   = $this->topRight($topRightMap);
        $bottomLeftImage = $this->bottomLeft($bottomLeftMap);

        /*
         * Adjust angle image
         */
        $this->log->debug('Adjusting Angle');
        $angleImage = $this->anglePoints($topRightImage, $bottomLeftImage);
        $this->adjustRotate($angleMap - $angleImage);

        /*
         * Check image again
         */
        $this->log->debug('Checking Image Again');
        $topRightImage   = $this->topRight($topRightMap);
        $bottomLeftImage = $this->bottomLeft($bottomLeftMap);

        /*
         * Adjust size image
         */
        $distanceCornersImage = $this->distancePoints($topRightImage, $bottomLeftImage);
        $p                    = 100 - ((100 * $distanceCornersImage) / $distanceCornersMap);
        $this->log->debug('Adjusting Image Size with Percentage: ' . $p);
        $this->adjustSize($p);

        /*
         * Check image again
         */
        $this->log->debug('Checking Image Again');
        $topRightImage   = $this->topRight($topRightMap);
        $bottomLeftImage = $this->bottomLeft($bottomLeftMap);

        $adjustX = $topRightImage->getX() - $topRightMap->getX();
        $adjustY = $bottomLeftImage->getY() - $bottomLeftMap->getY();

        if ($adjustX < 0) {
            $adjustX = 0;
        }
        if ($adjustY < 0) {
            $adjustY = 0;
        }

        $this->log->debug('Working on Targets');
        foreach ($map->targets() as $index => $target) {
            $this->log->debug('Working on Target: ' . $target->getId());

            if ($target instanceof TextTarget) {
                $this->log->debug('Target type: TEXT');

                $image = $this->textArea(
                    $target->getA()->moveX($adjustX)->moveY($adjustY),
                    $target->getB()->moveX($adjustX)->moveY($adjustY)
                );

//                $target->setImageBlob($image->getImageBlob());
                $target->readText($image);

            } else {
                if ($target instanceof RectangleTarget) {
                    $this->log->debug('Target type: RECTANGLE');

                    /*
                    $tempImage = new Imagick($this->imagePath);
                    $tempWidth = $target->getB()->getX() - $target->getA()->getX();
                    $tempHeight = $target->getB()->getY() - $target->getA()->getY();
                    $tempImage->setImageType(Imagick::IMGTYPE_GRAYSCALE);
                    $tempImage->cropImage($tempWidth, $tempHeight, $target->getA()->getX(), $target->getA()->getY());

                    $path = 'app/public/examples/target_' . $index . '_.jpg';
                    dump('Path: ' . $path);
                    if (File::exists(storage_path($path))) {
                        File::delete(storage_path($path));
                    }
                    $tempImage->writeImage(storage_path($path));

                    $path = 'app/public/examples/target_matte_' . $index . '_.jpg';
                    if (File::exists(storage_path($path))) {
                        File::delete(storage_path($path));
                    }
                    $tempImage->blurImage(5,3, Imagick::CHANNEL_MATTE);
                    $tempImage->writeImage(storage_path($path));
                    */


                    $area = $this->rectangleArea(
                        $target->getA()->moveX($adjustX)->moveY($adjustY),
                        $target->getB()->moveX($adjustX)->moveY($adjustY),
                        $target->getTolerance()
                    );

                    $target->setWhitePixelsCount($area->getWhitePixels());
                    $target->setWhitePixelsPercent($area->percentWhite());
                    $target->setBlackPixelsCount($area->getBlackPixels());
                    $target->setBlackPixelsPercent($area->percentBlack());
                    $target->setMarked($area->percentBlack() >= $target->getTolerance());
                }

                if ($target instanceof CircleTarget) {
                    /*
                    $area = $this->circleArea(
                        $target->getPoint()->moveX($adjustX)->moveY($adjustY),
                        $target->getRadius(), $target->getTolerance()
                    );

                    $target->setBlackPixelsPercent($area->percentBlack());
                    $target->setMarked($area->percentBlack() >= $target->getTolerance());
                    */
                }
            }

            $this->log->debug('Adding Target to the results');

            $result->addTarget($target);
        }

        if ($this->debug) {
            $this->debug();
        }

        $this->log->debug('Finishing Scan');
        $this->finish();

        return $result;
    }

    /**
     * Calculates distance between two points
     *
     * @param Point $a
     * @param Point $b
     *
     * @return float
     */
    protected function distancePoints(Point $a, Point $b): float
    {
        $diffX = $b->getX() - $a->getX();
        $diffY = $b->getY() - $a->getY();

        return sqrt(pow($diffX, 2) + pow($diffY, 2));
    }

    /**
     * Calculates angle between two points
     *
     * @param Point $a
     * @param Point $b
     *
     * @return float
     */
    protected function anglePoints(Point $a, Point $b): float
    {
        $diffX = $b->getX() - $a->getX();
        $diffY = $b->getY() - $a->getY();

        return rad2deg(atan($diffY / $diffX));
    }

    /**
     * Set debug flag
     *
     * @param boolean $debug
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * Create Result object from imagePath
     *
     * @param string $imagePath
     *
     * @return Result
     */
    protected function createResult($imagePath): Result
    {
        $info = getimagesize($imagePath);

        $result = new Result();
        $result->setDimensions($info[0], $info[1]);

        return $result;
    }

    public function setLog()
    {
        $this->log = Log::channel('PensoftScanner');
    }

    public function getLog()
    {
        return $this->log;
    }
}
