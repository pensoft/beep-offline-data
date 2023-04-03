<?php

namespace App\Vendors\ScannerTest\Map;

use App\Vendors\ScannerTest\Target\Target;
use App\Vendors\ScannerTest\Shape\Point;
use App\Vendors\ScannerTest\Target\CircleTarget;
use App\Vendors\ScannerTest\Target\RectangleTarget;
use App\Vendors\ScannerTest\Target\TextTarget;

class Map
{
    private array $limits;
    private array $targets;
    private float $areaAdjustment;

    /**
     * @param string $pathJson
     *
     * @return \App\Vendors\ScannerTest\Map\Map
     */
    public static function create(string $pathJson)
    {
        $mapJson = new self();
        $mapJson->setPathJson($pathJson);

        return $mapJson;
    }

    /**
     * @param string $pathJson
     */
    private function setPathJson(string $pathJson)
    {
        $json    = file_get_contents($pathJson);
        $decoded = json_decode($json, true);

        $this->limits  = $decoded['limits'];
        $this->targets = $decoded['targets'];
        $this->areaAdjustment = $decoded['area_adjustment_percentage'] ?? 100;
    }

    /**
     * Most point to the top/left
     *
     * @return Point
     */
    public function topLeft(): Point
    {
        $topLeft = $this->limits['topLeft'];

        return new Point($topLeft['x'], $topLeft['y']);
    }

    /**
     * Most point to the top/right
     *
     * @return Point
     */
    public function topRight(): Point
    {
        $topRight = $this->limits['topRight'];

        return new Point($topRight['x'], $topRight['y']);
    }

    /**
     * Most point to the bottom/left
     *
     * @return Point
     */
    public function bottomLeft(): Point
    {
        $bottomLeft = $this->limits['bottomLeft'];

        return new Point($bottomLeft['x'], $bottomLeft['y']);
    }

    /**
     * Most point to the bottom/right
     *
     * @return Point
     */
    public function bottomRight(): Point
    {
        $bottomRight = $this->limits['bottomRight'];

        return new Point($bottomRight['x'], $bottomRight['y']);
    }

    /**
     * Targets
     *
     * @return Target[]
     */
    public function targets(): array
    {
        $targets = [];

        foreach ($this->targets as $target) {
            $targetObject = null;
            if ($target['type'] == 'text') {
                $a = new Point($target['x1'], $target['y1']);
                $b = new Point($target['x2'], $target['y2']);

                $width = $b->getX() - $a->getX();
                $height = $b->getY() - $a->getY();
                $areaAdjustmentX = $this->areaAdjustment;
//                if ($areaAdjustmentX < 100) {
//                    $areaAdjustmentX = $this->areaAdjustment + (100 - $areaAdjustmentX) / 2;
//                }
                $movementX = $width - ($width * $areaAdjustmentX / 100);
                $movementY = $height - ($height * $this->areaAdjustment / 100);
                if ($movementX > 0 || $movementY > 0) {
                    $movementX = round($movementX / 2);
                    $movementY = round($movementY / 2);
                    $a->moveX($movementX)->moveY($movementY);
                    $b->moveX(-$movementX)->moveY(-$movementY);
                }

                $targetObject = new TextTarget($target['id'], $a, $b);
            } elseif ($target['type'] == 'rectangle') {
                $a = new Point($target['x1'], $target['y1']);
                $b = new Point($target['x2'], $target['y2']);

                $width = $b->getX() - $a->getX();
                $movement = $width - ($width * $this->areaAdjustment / 100);
                if ($movement > 0) {
                    $movement = round($movement / 2);
                    $a->moveX($movement)->moveY($movement);
                    $b->moveX(-$movement)->moveY(-$movement);
                }

                $targetObject = new RectangleTarget($target['id'], $a, $b);
            } elseif ($target['type'] == 'circle') {
                $targetObject = new CircleTarget(
                    $target['id'],
                    new Point($target['x'], $target['y']), $target['radius']
                );
            }

            $tolerance = $target['tolerance'] ?? $targetObject->getTolerance();
            $targetObject->setTolerance($tolerance);

            $targets[] = $targetObject;
        }

        return $targets;
    }

    private function MovePoints(Point $point): Point
    {

    }
}
