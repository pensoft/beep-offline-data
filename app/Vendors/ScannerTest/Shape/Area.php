<?php

namespace App\Vendors\ScannerTest\Shape;


class Area
{
    /**
     * Total number of pixels
     *
     * @var int
     */
    private int $pixels;

    /**
     * Number of white pixels
     *
     * @var float
     */
    private float $whitePixels;

    /**
     * Number of black pixels
     *
     * @var float
     */
    private float $blackPixels;

    /**
     * Area constructor.
     *
     * @param int $pixels
     * @param int $whitePixels
     * @param int $blackPixels
     */
    public function __construct(int $pixels, int $whitePixels, int $blackPixels)
    {
        $this->pixels      = $pixels;
        $this->whitePixels = $whitePixels;
        $this->blackPixels = $blackPixels;
    }

    /**
     * Percentage of black pixels
     *
     * @returns float
     */
    public function percentBlack(): float
    {
        return 100 * $this->blackPixels / $this->pixels;
    }

    /**
     * Percentage of white pixels
     *
     * @returns float
     */
    public function percentWhite(): float
    {
        return 100 * $this->whitePixels / $this->pixels;
    }

    /**
     * @return int
     */
    public function getPixels()
    {
        return $this->pixels;
    }

    /**
     * @return float
     */
    public function getBlackPixels(): float
    {
        return $this->blackPixels;
    }

    /**
     * @return float
     */
    public function getWhitePixels(): float
    {
        return $this->whitePixels;
    }
}
