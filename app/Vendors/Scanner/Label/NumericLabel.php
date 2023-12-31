<?php

namespace App\Vendors\Scanner\Label;

use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Converter\Dpm;
use App\Vendors\Scanner\Schema\Schema;
use Imagick;
use thiagoalessio\TesseractOCR\TesseractOCR;

class NumericLabel extends Label
{
    private array $whitelistCharacters = ['-', '_', ',', '.'];

    /**
     * NumericLabel constructor.
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
     * @param string $color
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     */
    public function markLabelImage(string $color = '#00CC00'): void
    {
        parent::markLabelImage($color);
    }

    /**
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

        $path = $folder . '/';
        $path .= $this->getParentCategoryId() . '_' . $this->getCategoryId() . '_' . rand(10, 10000) . '.jpg';
        $image->writeImage($path);

        $value = null;
        // Get the scan result from external OCR engines
        $searchKey = $this->getLabelKey($this->getTextLabel());
        $this->getLog()->debug('External results search key: ' . $searchKey);
        if (!empty($externalScanResults[$searchKey])) {
            $externalScan = $externalScanResults[$searchKey];
            $this->getLog()->debug('EXTERNAL SCAN: ' . json_encode($externalScan));

            $value = implode(' ', array_column($externalScan, 'value'));
            $this->getLog()->debug('EXTERNAL SCAN VALUE: ' . $value);

            $value = $this->formatValueNumber($value);
            $this->getLog()->debug('EXTERNAL SCAN VALUE FORMATTED: ' . $value);
        } else {
            // If there is no scan result, scan with Tesseract
            $ocr = new TesseractOCR($path);

            if ($this->getType() === 'number') {
                $ocr->psm(7);
                $ocr->allowlist(range(0, 9), implode('', $this->whitelistCharacters));
            } else {
                $ocr->psm(10);
                $ocr->allowlist(range(0, 9));
            }

            $value = $ocr->run();
            $this->getLog()->debug('TESSERACT SCAN VALUE: ' . $value);
        }

        $this->setValue($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    private function formatValueNumber(string $value): string
    {
        $value = preg_replace("/[^0-9 " . implode('', $this->whitelistCharacters) . "]/", "", $value);

        // force delete % symbol
        $value = str_replace('%', '', $value);

        // format floats
        $value = str_replace(',', '.', $value);

        return trim($value);
    }
}
