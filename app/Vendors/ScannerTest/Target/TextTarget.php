<?php

namespace App\Vendors\ScannerTest\Target;

use App\Vendors\ScannerTest\Shape\Point;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Imagick;

class TextTarget extends Target
{
    /**
     * Pointer Top/Left
     *
     * @var Point
     */
    private Point $a;

    /**
     * Pointer Bottom/Right
     *
     * @var Point
     */
    private Point $b;

    /**
     * Image
     *
     * @var string
     */
    private string $imageBlob;

    /**
     * TextTarget constructor.
     *
     * @param string                           $id
     * @param \App\Vendors\ScannerTest\Shape\Point $a
     * @param \App\Vendors\ScannerTest\Shape\Point $b
     */
    public function __construct(string $id, Point $a, Point $b)
    {
        $this->id = $id;
        $this->a  = $a;
        $this->b  = $b;
    }

    /**
     * Get Pointer Top/Left
     *
     * @return Point
     */
    public function getA(): Point
    {
        return $this->a;
    }

    /**
     * Get Pointer Bottom/Right
     *
     * @return Point
     */
    public function getB(): Point
    {
        return $this->b;
    }

    /**
     * Set image blob
     *
     * @param string
     */
    public function setImageBlob(string $imageBlob)
    {
        $this->imageBlob = $imageBlob;
    }

    /**
     * Set image blob
     *
     * @return string
     */
    public function getImageBlob(): string
    {
        return $this->imageBlob;
    }

    /**
     * @throws \ImagickException
     * @throws \thiagoalessio\TesseractOCR\TesseractOcrException
     */
    public function readText(Imagick $image)
    {
        /*
         * PSM: list of supported page segmentation modes
        0    Orientation and script detection (OSD) only.
        1    Automatic page segmentation with OSD.
        2    Automatic page segmentation, but no OSD, or OCR.
        3    Fully automatic page segmentation, but no OSD. (Default)
        4    Assume a single column of text of variable sizes.
        5    Assume a single uniform block of vertically aligned text.
        6    Assume a single uniform block of text.
        7    Treat the image as a single text line.
        8    Treat the image as a single word.
        9    Treat the image as a single word in a circle.
        10    Treat the image as a single character.
        11    Sparse text. Find as much text as possible in no particular order.
        12    Sparse text with OSD.
        13    Raw line. Treat the image as a single text line, bypassing hacks that are Tesseract-specific.
        */

        //        $this->setValue('text');
        //        $image = new Imagick();
        //        dump('Blob: ' . base64_encode($this->getImageBlob()));
        //        dump('Blob decoded: ' . base64_decode($this->getImageBlob()));
        $path = storage_path('app/public/examples/texts/' . $this->id . '.jpg');

//        $image->resizeImage($image->getImageWidth() * 5, $image->getImageHeight() * 5, Imagick::FILTER_LANCZOS, 1);
        $image->sharpenImage(0, 100);
        $image->writeImage($path);

        $ocr = new TesseractOCR($path);
        if (str_contains(mb_strtolower($this->id), 'numeric')) {
            if (str_contains(mb_strtolower($this->id), 'single')) {
                $ocr->psm(10);
            } else {
                //
                $ocr->psm(7);
            }
            //            $ocr->psm(6);
            //            $ocr->whitelist(range(0, 9));
            $ocr->allowlist(range(0, 9), ',.');
        } else {
            $ocr->psm(6);
        }
        $this->setValue($ocr->run());
    }
}
