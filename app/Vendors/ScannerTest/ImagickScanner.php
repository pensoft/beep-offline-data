<?php

namespace App\Vendors\ScannerTest;

use Imagick;
use ImagickDraw;
use ImagickPixel;
use App\Vendors\ScannerTest\Shape\Area;
use App\Vendors\ScannerTest\Shape\Point;
use App\Vendors\ScannerTest\Builder\ScannerBuilder;

class ImagickScanner extends ScannerBuilder
{
    /**
     * @var \Imagick
     */
    private Imagick $original;

    /**
     * @var \Imagick
     */
    private Imagick $imagick;

    /**
     * @var \ImagickDraw
     */
    private ImagickDraw $draw;

    /**
     * ImagickScanner constructor.
     */
    public function __construct()
    {
        $this->setLog();
        $this->draw = new ImagickDraw();
        $this->draw->setFontSize(6);
        $this->draw->setFillOpacity(0.4);
        $this->draw->setStrokeWidth(1);
        $this->draw->setStrokeOpacity(1);
        $this->draw->setFillOpacity(1);
        $this->draw->setFillColor(new ImagickPixel('#00000000'));
    }

    /**
     * Create or return instance Imagick
     *
     * @return \Imagick
     * @throws \ImagickException
     */
    private function getImagick(): Imagick
    {
        if (is_null($this->imagick)) {
            $this->original = new Imagick($this->imagePath);

            $this->imagick = new Imagick($this->imagePath);
            $this->imagick->setResolution(300, 300);
            $this->imagick->medianFilterImage(2);
            $this->imagick->setImageCompression(imagick::COMPRESSION_JPEG);
            $this->imagick->setImageCompressionQuality(100);
            $this->imagick->blackThresholdImage('#FFFFFF');
            $this->imagick->whiteThresholdImage('#000000');
        }

        return $this->imagick;
    }

    /**
     * Most point to the top/right
     *
     * @param \App\Vendors\ScannerTest\Shape\Point $near
     *
     * @return \App\Vendors\ScannerTest\Shape\Point
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     */
    protected function topRight(Point $near): Point
    {
        $imagick = $this->getImagick();

        $x = $near->getX() - 20;
        $y = $near->getY() - 20;

        $first = new Point($x > 0 ? $x : 0, $y > 0 ? $y : 0);

        $x = $near->getX() + 20;
        $y = $near->getY() + 20;

        $last = new Point($x > $imagick->getImageWidth() ? $imagick->getImageWidth() : $x,
            $y > $imagick->getImageHeight() ? $imagick->getImageHeight() : $y);

        $point = new Point($near->getX(), $near->getY());

        //Add draw debug
        $this->draw->setStrokeColor(new ImagickPixel('#00CC00'));
        $this->draw->rectangle($first->getX(), $first->getY(), $last->getX(), $last->getY());

        for ($y = $first->getY(); $y != $last->getY(); $y++) {
            for ($x = $first->getX(); $x != $last->getX(); $x++) {
                $color = $imagick->getImagePixelColor($x, $y)->getColor();

                if ($color['r'] <= 5 && $color['g'] <= 5 && $color['b'] <= 5) {
                    if ($x >= $point->getX()) {
                        $point->setX($x);
                    }

                    if ($y <= $point->getY()) {
                        $point->setY($y);
                    }
                }
            }
        }

        //Debug draw
        $this->draw->circle($point->getX(), $point->getY(), $point->getX() + 2, $point->getY());

        return $point;
    }

    /**
     * Most point to the bottom/left
     *
     * @param \App\Vendors\ScannerTest\Shape\Point $near
     *
     * @return \App\Vendors\ScannerTest\Shape\Point
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     */
    protected function bottomLeft(Point $near): Point
    {
        $imagick = $this->getImagick();

        $x = $near->getX() - 20;
        $y = $near->getY() - 20;

        $first = new Point($x > 0 ? $x : 0, $y > 0 ? $y : 0);

        $x = $near->getX() + 20;
        $y = $near->getY() + 20;

        $last = new Point($x > $imagick->getImageWidth() ? $imagick->getImageWidth() : $x,
            $y > $imagick->getImageHeight() ? $imagick->getImageHeight() : $y);

        $point = new Point($near->getX(), $near->getY());

        //Add draw debug
        $this->draw->setStrokeColor(new ImagickPixel('#00CC00'));
        $this->draw->rectangle($first->getX(), $first->getY(), $last->getX(), $last->getY());

        for ($y = $first->getY(); $y != $last->getY(); $y++) {
            for ($x = $first->getX(); $x != $last->getX(); $x++) {
                $color = $imagick->getImagePixelColor($x, $y)->getColor();

                if ($color['r'] <= 5 && $color['g'] <= 5 && $color['b'] <= 5) {
                    if ($x <= $point->getX()) {
                        $point->setX($x);
                    }

                    if ($y >= $point->getY()) {
                        $point->setY($y);
                    }
                }
            }
        }

        //Debug draw
        $this->draw->circle($point->getX(), $point->getY(), $point->getX() + 2, $point->getY());

        return $point;
    }

    /**
     * Increases or decreases image size
     *
     * @param float $percent
     *
     * @return float
     * @throws \ImagickException
     */
    protected function adjustSize(float $percent): float
    {
        $imagick = $this->getImagick();

        $widthAdjusted = $imagick->getImageWidth() + (($imagick->getImageWidth() * $percent) / 100);
        $heightAdjust  = $imagick->getImageHeight() + (($imagick->getImageHeight() * $percent) / 100);

        $this->imagick->resizeImage($widthAdjusted, $heightAdjust, Imagick::FILTER_POINT, 0, false);

        $this->original->resizeImage($widthAdjusted, $heightAdjust, Imagick::FILTER_POINT, 0, false);

        return $percent;
    }

    /**
     * Rotate image
     *
     * @param float $degrees
     *
     * @return float
     * @throws \ImagickException
     */
    protected function adjustRotate(float $degrees): float
    {
        if ($degrees < 0) {
            $degrees = 360 + $degrees;
        }

        $imagick = $this->getImagick();

        $originalWidth  = $imagick->getImageWidth();
        $originalHeight = $imagick->getImageHeight();

        $this->imagick->rotateImage('#FFFFFF', $degrees);
        $this->imagick->setImagePage($imagick->getimageWidth(), $imagick->getimageheight(), 0, 0);
        $this->imagick->cropImage($originalWidth, $originalHeight, ($imagick->getimageWidth() - $originalWidth) / 2,
            ($imagick->getimageHeight() - $originalHeight) / 2);

        $this->original->rotateImage('#FFFFFF', $degrees);
        $this->original->setImagePage($imagick->getimageWidth(), $imagick->getimageheight(), 0, 0);
        $this->original->cropImage($originalWidth, $originalHeight, ($imagick->getimageWidth() - $originalWidth) / 2,
            ($imagick->getimageHeight() - $originalHeight) / 2);

        return $degrees;
    }

    /**
     * Generate file debug.jpg with targets, topRight and buttonLeft
     *
     * @throws \ImagickException
     */
    public function debug()
    {
        $imagick = $this->getImagick();
        $imagick->drawImage($this->draw);
        $imagick->writeImage($this->debugPath);
    }

    /**
     * Returns pixel analysis in a rectangular area
     *
     * @param \App\Vendors\ScannerTest\Shape\Point $a
     * @param \App\Vendors\ScannerTest\Shape\Point $b
     * @param float                            $tolerance
     *
     * @return \App\Vendors\ScannerTest\Shape\Area
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    protected function rectangleArea(Point $a, Point $b, float $tolerance): Area
    {
        $imagick = $this->getImagick();

        $width  = $b->getX() - $a->getX();
        $height = $b->getY() - $a->getY();

        $pixels = $imagick->exportImagePixels($a->getX(), $a->getY(), $width, $height, 'B', Imagick::PIXEL_CHAR);

        $counts = array_count_values($pixels);
        ksort($counts);

//        $whites = isset($counts[255]) ? $counts[255] : 0;

//        $blacks = array_sum($counts) - $whites;

        $blacks = 0;
        $whites = 0;
        foreach ($counts as $color => $countPixels) {
            if ($color < 253) {
                $blacks += $countPixels;
            } else {
                $whites += $countPixels;
            }
        }

        $area = new Area(count($pixels), $whites, $blacks);

        //Add draw debug
        $strokeColor = $area->percentBlack() >= $tolerance ? new ImagickPixel('#00CC00') : new ImagickPixel('#0000CC');
        $this->draw->setStrokeColor($strokeColor);
        $this->draw->rectangle($a->getX(), $a->getY(), $b->getX(), $b->getY());

        return $area;
    }

    /**
     * Returns pixel analysis in a circular area
     *
     * @param Point $a
     * @param float $radius
     * @param float $tolerance
     *
     * @return Area
     */
    protected function circleArea(Point $a, float $radius, float $tolerance): Area
    {
        return true;
    }

    /**
     * Returns image blob in a rectangular area
     *
     * @param \App\Vendors\ScannerTest\Shape\Point $a
     * @param \App\Vendors\ScannerTest\Shape\Point $b
     *
     * @return Imagick
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    protected function textArea(Point $a, Point $b): Imagick
    {
        $width  = $b->getX() - $a->getX();
        $height = $b->getY() - $a->getY();

        $region = $this->original->getImageRegion($width, $height, $a->getX(), $a->getY());

        //Add draw debug
        $this->draw->setStrokeColor(new ImagickPixel('#0000CC'));
        $this->draw->rectangle($a->getX(), $a->getY(), $b->getX(), $b->getY());

        return $region;
    }

    /**
     * Finish processes
     *
     * @throws \ImagickException
     */
    protected function finish()
    {
        $this->getImagick()->clear();
        $this->original->clear();
    }

    /**
     * Set image path and Imagick objects
     *
     * @param mixed $imagePath
     *
     * @throws \ImagickException
     */
    public function setImagePath(mixed $imagePath)
    {
        $this->imagePath = $imagePath;

        $this->original = new Imagick($this->imagePath);

        $this->imagick = new Imagick($this->imagePath);
        $this->imagick->setImageType(Imagick::IMGTYPE_GRAYSCALEMATTE);
    }
}
