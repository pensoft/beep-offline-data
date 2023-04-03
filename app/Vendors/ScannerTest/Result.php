<?php

namespace App\Vendors\ScannerTest;

use App\Vendors\ScannerTest\Target\CircleTarget;
use App\Vendors\ScannerTest\Target\Target;
use App\Vendors\ScannerTest\Target\TextTarget;

class Result
{
    /**
     * Path Image
     *
     * @var string
     */
    private string $imagePath;

    /**
     * MIME Image
     *
     * @var string
     */
    private string $imageMime;

    /**
     * Width Image
     *
     * @var int
     */
    private int $width;

    /**
     * Height Image
     *
     * @var int
     */
    private int $height;

    /**
     * Targets
     *
     * @var Target[]
     */
    private $targets = [];

    /**
     * @param int $width
     * @param int $height
     */
    public function setDimensions(int $width, int $height)
    {
        $this->width  = $width;
        $this->height = $height;
    }

    /**
     * Add target
     *
     * @param Target $target
     *
     * @return void
     */
    public function addTarget(Target $target)
    {
        $this->targets[] = $target;
    }

    /**
     * Get target
     *
     * @return Target[]
     */
    public function getTargets(): array
    {
        return $this->targets;
    }

    /**
     * Set mime image
     *
     * @param string $imageMime
     */
    public function setImageMime(string $imageMime)
    {
        $this->imageMime = $imageMime;
    }

    /**
     * Set Path image
     *
     * @param string $imagePath
     */
    public function setImagePath(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    /**
     * To Array
     *
     * @return array
     */
    public function toArray(): array
    {
        $filtered = array_filter($this->targets, function (Target $target) {
            return !($target instanceof CircleTarget);
        });

        $targets = array_map(function (Target $item) {
            if ($item instanceof TextTarget) {
                $result = [
                    'id'    => $item->getId(),
                    'value' => $item->getValue(),
                ];
            } else {
                $result = [
                    'id'                   => $item->getId(),
                    'marked'               => $item->isMarked() ? 'yes' : 'no',
                    'white_pixels_count'   => $item->getWhitePixelsCount(),
                    'white_pixels_percent' => round($item->getWhitePixelsPercent(), 2) . '%',
                    'black_pixels_count'   => $item->getBlackPixelsCount(),
                    'black_pixels_percent' => '<b>' . round($item->getBlackPixelsPercent(), 2) . '%</b>',
                ];
            }

            return $result;
        }, $filtered);

        return compact('targets');
    }

    /**
     * To Json
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
