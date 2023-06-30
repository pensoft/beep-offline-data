<?php

namespace App\Vendors\Scanner\Label;

use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Converter\Dpm;
use App\Vendors\Scanner\Schema\Schema;
use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;

class TextLabel extends Label
{
    /**
     * TextLabel constructor.
     *
     * @param \Imagick                           $image
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     * @param \App\Vendors\Scanner\Config\Config $config
     * @param \App\Vendors\Scanner\Converter\Dpm $dpm
     * @param mixed                              $labelData
     * @param string                             $folder
     *
     * @throws \ImagickDrawException
     */
    public function __construct(
        Imagick $image,
        Schema $schema,
        Config $config,
        Dpm $dpm,
        mixed $labelData,
        string $folder
    ) {
        parent::__construct($image, $schema, $config, $dpm, $labelData, $folder);
    }

    /**
     * @param string $folder
     * @param array  $externalScanResults
     *
     * @return void
     * @throws \ImagickException
     * @throws \thiagoalessio\TesseractOCR\TesseractOcrException
     */
    public function scan(string $folder, array $externalScanResults = []): void
    {
        $labelImage = $this->getLabelImage();
        $this->readText($labelImage, $folder, $externalScanResults);
        $this->getLog()->debug('SCANNED TEXT: ' . $this->getValue());
        $this->getLog()->debug('_______________________________________________');


        $blob = 'data:image/' . $labelImage->getImageFormat() . ';base64,' . base64_encode($labelImage->getImageBlob());
        $this->setBlob($blob);
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return parent::getResult();
    }

    /**
     * @return \Imagick
     * @throws \ImagickException
     */
    public function getLabelImage(): Imagick
    {
        return parent::getLabelImage();
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     */
    public function markLabelImage(string $color = '#00CC00'): void
    {
        parent::markLabelImage($color);
    }

    /**
     * @param \Imagick $image
     * @param string   $folder
     * @param array    $externalScanResults
     *
     * @return void
     * @throws \ImagickException
     * @throws \thiagoalessio\TesseractOCR\TesseractOcrException
     */
    public function readText(Imagick $image, string $folder, array $externalScanResults = []): void
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

        $path = $folder . '/' . $this->getParentCategoryId() . '_' . $this->getCategoryId() .
                '_' . rand(10, 10000) . '.jpg';
        //        $image->resizeImage($image->getImageWidth() * 5, $image->getImageHeight() * 5, Imagick::FILTER_LANCZOS, 1);
        //        $image->sharpenImage(0, 25);
        $image->writeImage($path);

        $value = null;
        // Get the scan result from external OCR engines
        $searchKey = $this->getLabelKey($this->getTextLabel());
        if (!empty($externalScanResults[$searchKey])) {
            $externalScan = $externalScanResults[$searchKey];
            $this->getLog()->debug('EXTERNAL SCAN: ' . json_encode($externalScan));

            $value = implode(' ', array_column($externalScan, 'value'));
            $this->getLog()->debug('EXTERNAL SCAN VALUE: ' . $value);
        }

        // If there is no scan result, scan with Tesseract
        if (empty($value)) {
            $ocr = new TesseractOCR($path);

            $languages = !empty($this->getSchema()->getLanguages()) ? $this->getSchema()->getLanguages() : ['eng'];
            $ocr->lang(implode(', ', $languages))->psm(6);

            $value = $ocr->run();
            $this->getLog()->debug('TESSERACT SCAN VALUE: ' . $value);
        }

        $this->setValue($value);
    }
}
