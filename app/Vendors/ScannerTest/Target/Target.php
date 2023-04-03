<?php

namespace App\Vendors\ScannerTest\Target;

abstract class Target
{
    /**
     * Store results if the target was marked
     *
     * @var boolean
     */
    protected bool $marked = false;

    /**
     * Identifier
     *
     * @var string
     */
    protected string $id;

    /**
     * Identifier
     *
     * @var string
     */
    protected string $value;

    /**
     * Black pixels percentage compared to whites to consider marked
     *
     * @var int
     */
    protected int $tolerance = 24;

    /**
     * Black pixels percentage
     *
     * @var float
     */
    protected float $blackPixelsPercent = 0;

    /**
     * White pixels percentage
     *
     * @var float
     */
    protected float $whitePixelsPercent = 0;

    /**
     * Pixels count
     *
     * @var int
     */
    protected int $pixels = 0;

    /**
     * Black pixels count
     *
     * @var int
     */
    protected int $blackPixelsCount = 0;

    /**
     * White pixels count
     *
     * @var int
     */
    protected int $whitePixelsCount = 0;

    /**
     * Checks if the target was marked
     *
     * @return boolean
     */
    public function isMarked(): bool
    {
        return $this->marked;
    }

    /**
     * Tells whether the target was marked
     *
     * @param boolean $marked
     */
    public function setMarked(bool $marked)
    {
        $this->marked = $marked;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue(string $value)
    {
        $this->value = $value;
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
    public function setTolerance(int $tolerance)
    {
        $this->tolerance = $tolerance;
    }

    /**
     * @return float
     */
    public function getBlackPixelsPercent(): float
    {
        return $this->blackPixelsPercent;
    }

    /**
     * @param float $blackPixelsPercent
     */
    public function setBlackPixelsPercent(float $blackPixelsPercent)
    {
        $this->blackPixelsPercent = $blackPixelsPercent;
    }

    /**
     * @return float
     */
    public function getWhitePixelsPercent(): float
    {
        return $this->whitePixelsPercent;
    }

    /**
     * @param float $whitePixelsPercent
     */
    public function setWhitePixelsPercent(float $whitePixelsPercent)
    {
        $this->whitePixelsPercent = $whitePixelsPercent;
    }

    /**
     * @return int
     */
    public function getBlackPixelsCount(): int
    {
        return $this->blackPixelsCount;
    }

    /**
     * @param int $blackPixelsCount
     */
    public function setBlackPixelsCount(int $blackPixelsCount)
    {
        $this->blackPixelsCount = $blackPixelsCount;
    }

    /**
     * @return int
     */
    public function getWhitePixelsCount(): int
    {
        return $this->whitePixelsCount;
    }

    /**
     * @param int $whitePixelsCount
     */
    public function setWhitePixelsCount(int $whitePixelsCount)
    {
        $this->whitePixelsCount = $whitePixelsCount;
    }

}
