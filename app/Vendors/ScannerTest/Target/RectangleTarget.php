<?php

namespace App\Vendors\ScannerTest\Target;

use App\Vendors\ScannerTest\Shape\Point;

class RectangleTarget extends Target
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
     * RectangleTarget constructor.
     *
     * @param string                           $id
     * @param \App\Vendors\ScannerTest\Shape\Point $a
     * @param \App\Vendors\ScannerTest\Shape\Point $b
     */
    public function __construct(string $id, Point $a, Point $b)
    {
        $this->id = $id;
        $this->a = $a;
        $this->b = $b;
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

}
