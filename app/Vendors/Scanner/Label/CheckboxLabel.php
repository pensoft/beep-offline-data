<?php

namespace App\Vendors\Scanner\Label;

use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Converter\Dpm;
use App\Vendors\Scanner\Schema\Schema;
use Imagick;

class CheckboxLabel extends Label
{
    /** @var float */
    protected float $tolerance;

    /** @var int */
    private int $pixels = 0;

    /** @var int */
    protected int $blackPixels = 0;

    /** @var int */
    protected int $whitePixels = 0;

    /**
     * CheckboxLabel constructor.
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

        $this->getLog()->debug('TEXT LABEL DATA: ' . json_encode($labelData));

        $this->setTolerance($this->getSchema()->getTolerance());
    }

    /**
     * @param string $folder
     *
     * @throws \ImagickException
     */
    public function scan(string $folder): void
    {
        $labelImage = $this->getLabelImage();

        $labelImage->writeImage(
            $folder . '/' . $this->getParentCategoryId() . '_' . $this->getCategoryId() . '_' . rand(10, 10000) . '.jpg'
        );

        $width  = $labelImage->getImageWidth();
        $height = $labelImage->getImageHeight();
        $pixels = $labelImage->exportImagePixels(0, 0, $width, $height, 'I', Imagick::PIXEL_CHAR);

        $this->setPixels(count($pixels));
        $pixels = array_count_values($pixels);
        ksort($pixels);

        $blackPixels = 0;
        $whitePixels = 0;
        foreach ($pixels as $color => $countPixels) {
            if ($color < 128) {
                $blackPixels += $countPixels;
            } else {
                $whitePixels += $countPixels;
            }
        }

        $this->setBlackPixels($blackPixels);
        $this->setWhitePixels($whitePixels);

        $this->getLog()->debug('CHECKBOX BLACK PXL %:' . $this->getBlackPixelsPercentage());

        $this->setValue((int)($this->getBlackPixelsPercentage() >= $this->getTolerance()));
        $this->setChecked(($this->getBlackPixelsPercentage() >= $this->getTolerance()));

        $this->getLog()->debug('CHECKBOX VALUE:' . (int)($this->getBlackPixelsPercentage() >= $this->getTolerance()));
        $this->getLog()->debug('_______________________________________________');

        $blob = 'data:image/' . $labelImage->getImageFormat() . ';base64,' . base64_encode($labelImage->getImageBlob());
        $this->setBlob($blob);
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        $result          = parent::getResult();
        $result['value'] = (int)$this->isChecked();

        return $result;
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
     * @return int
     */
    public function getTolerance(): int
    {
        return $this->tolerance;
    }

    /**
     * @param int $tolerance
     */
    public function setTolerance(int $tolerance): void
    {
        $this->tolerance = $tolerance;
    }

    /**
     * @return int
     */
    public function getPixels(): int
    {
        return $this->pixels;
    }

    /**
     * @param int $count
     */
    public function setPixels(int $count): void
    {
        $this->pixels = $count;
    }

    /**
     * @return int
     */
    public function getBlackPixels(): int
    {
        return $this->blackPixels;
    }

    /**
     * @param int $count
     */
    public function setBlackPixels(int $count): void
    {
        $this->blackPixels = $count;
    }

    /**
     * @return int
     */
    public function getWhitePixels(): int
    {
        return $this->whitePixels;
    }

    /**
     * @param int $count
     */
    public function setWhitePixels(int $count): void
    {
        $this->whitePixels = $count;
    }

    /**
     * @return float
     */
    public function getWhitePixelsPercentage(): float
    {
        return !empty($this->getPixels()) ? round($this->getWhitePixels() / $this->getPixels() * 100, 2) : 0;
    }

    /**
     * @return float
     */
    public function getBlackPixelsPercentage(): float
    {
        return !empty($this->getPixels()) ? round($this->getBlackPixels() / $this->getPixels() * 100, 2) : 0;
    }
}
