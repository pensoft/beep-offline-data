<?php

namespace App\Vendors\ScannerTest\Shape;

class Point
{
    /**
     * Value X
     *
     * @var float
     */
    private float $x;

    /**
     * Value Y
     *
     * @var float
     */
    private float $y;

    /**
     * Point constructor.
     *
     * @param float $x
     * @param float $y
     */
    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Position X
     *
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * Position Y
     *
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * Position X
     *
     * @param float $x
     */
    public function setX(float $x)
    {
        $this->x = $x;
    }

    /**
     * Position Y
     *
     * @param float $y
     */
    public function setY(float $y)
    {
        $this->y = $y;
    }

    /**
     * Move the point at $position on the X axis
     *
     * @param int $position
     * @return Point
     */
    public function moveX(int $position): Point
    {
        $this->x = $this->x + $position;

        return $this;
    }

    /**
     * Move the point at $position on the Y axis
     *
     * @param int $position
     * @return Point
     */
    public function moveY($position): Point
    {
        $this->y = $this->y + $position;

        return $this;
    }

}
