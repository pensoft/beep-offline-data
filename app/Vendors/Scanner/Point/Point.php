<?php

namespace App\Vendors\Scanner\Point;

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
     *
     * @return Point
     */
    public function moveX(int $position): Point
    {
        $this->x += $position;

        return $this;
    }

    /**
     * Move the point at $position on the Y axis
     *
     * @param int $position
     *
     * @return Point
     */
    public function moveY(int $position): Point
    {
        $this->y += $position;

        return $this;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return $this
     */
    public function move(int $x, int $y): Point
    {
        $this->x += $x;
        $this->y += $y;

        return $this;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $a
     * @param \App\Vendors\Scanner\Point\Point $b
     * @param bool                             $absolute
     *
     * @return float
     */
    public static function getWidth(Point $a, Point $b, bool $absolute = true): float
    {
        $width = $b->getX() - $a->getX();
        if ($absolute) {
            $width = abs($width);
        }

        return $width;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $a
     * @param \App\Vendors\Scanner\Point\Point $b
     * @param bool                             $absolute
     *
     * @return float
     */
    public static function getHeight(Point $a, Point $b, bool $absolute = true): float
    {
        $height = $b->getY() - $a->getY();
        if ($absolute) {
            $height = abs($height);
        }

        return $height;
    }
}
