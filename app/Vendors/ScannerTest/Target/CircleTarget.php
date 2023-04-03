<?php

namespace App\Vendors\ScannerTest\Target;

use App\Vendors\ScannerTest\Shape\Point;

class CircleTarget extends Target
{
    /**
     * Center point
     *
     * @var Point
     */
    private Point $point;

    /**
     * Radius
     *
     * @var float
     */
    private float $radius;

    /**
     * CircleTarget constructor.
     *
     * @param                                  $id
     * @param \App\Vendors\ScannerTest\Shape\Point $point
     * @param                                  $radius
     */
    public function __construct($id, Point $point, $radius)
    {
        $this->id = $id;
        $this->point = $point;
        $this->radius = $radius;
    }

    /**
     * Get center pointer
     *
     * @return Point
     */
    public function getPoint(): Point
    {
        return $this->point;
    }

    /**
     * Get radius circle
     *
     * @return float
     */
    public function getRadius(): float
    {
        return $this->radius;
    }



}
