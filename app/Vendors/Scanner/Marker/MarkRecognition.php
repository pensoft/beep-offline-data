<?php

namespace App\Vendors\Scanner\Marker;

use App\Vendors\Scanner\Point\Point;
use App\Vendors\Scanner\Schema\Schema;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use JetBrains\PhpStorm\ArrayShape;

class MarkRecognition
{
    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $topLeft;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $topRight;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $bottomLeft;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $bottomRight;

    private Imagick $image;

    private Imagick $markerImage;

    private ImagickDraw $draw;
    private ImagickDraw $regionDraw;

    private Schema $schema;

    private string $folder;

    private float $dpmX;
    private float $dpmY;

    private float $widthSearchRation  = 0.15;
    private float $heightSearchRation = 0.1;

    /**
     * MarkRecognition constructor.
     *
     * @param \Imagick                           $imagick
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     * @param string                             $folder
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function __construct(Imagick $imagick, Schema $schema, string $folder)
    {
        $this->setFolder($folder);
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => env('SCANNER_LOG_MODE', 'debug'),
            ]
        );

        $this->setImage($imagick);
        $this->setSchema($schema);
        $this->setDraw();
        $this->initializeMarkerImage();
        $this->initializeDpm();
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function recognizeMarkers(bool $drawMarks = true)
    {
        $this->setTopLeftMarker();
        $this->setTopRightMarker();
        $this->setBottomLeftMarker();
        $this->setBottomRightMarker();

        if (!empty($drawMarks)) {
            $this->getImage()->drawImage($this->getDraw());
        }

        $this->getImage()->writeImage($this->getFolder() . '/' . 'debug_markers.jpg');
    }

    /**
     * @return \array[][][]
     */
    public function getMarkers(): array
    {
        return [
            "markers" => [
                "top"    => [
                    "left"  => [
                        "x" => $this->getTopLeft()->getX(),
                        "y" => $this->getTopLeft()->getY(),
                    ],
                    "right" => [
                        "x" => $this->getTopRight()->getX(),
                        "y" => $this->getTopRight()->getY(),
                    ],
                ],
                "bottom" => [
                    "left"  => [
                        "x" => $this->getBottomLeft()->getX(),
                        "y" => $this->getBottomLeft()->getY(),
                    ],
                    "right" => [
                        "x" => $this->getBottomRight()->getX(),
                        "y" => $this->getBottomRight()->getY(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function setTopLeftMarker()
    {
        $position    = 'top_left';
        $markerImage = clone $this->getMarkerImage();
        $this->resizeMarker(
            $markerImage,
            $this->getSchema()->getSchema()->markers->top->left->width,
            $this->getSchema()->getSchema()->markers->top->left->height,
            $position
        );

        $coordinates = $this->getMarkRecognition($markerImage, 0, 0, $position);

        $this->setTopLeft(new Point($coordinates['x'], $coordinates['y']));
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function setTopRightMarker()
    {
        $position    = 'top_right';
        $markerImage = clone $this->getMarkerImage();
        $this->resizeMarker(
            $markerImage,
            $this->getSchema()->getSchema()->markers->top->left->width,
            $this->getSchema()->getSchema()->markers->top->left->height,
            $position
        );

        $offsetX     = $this->getImage()->getImageWidth() * (1 - $this->widthSearchRation);
        $coordinates = $this->getMarkRecognition($markerImage, $offsetX, 0, $position);

        $this->setTopRight(new Point($coordinates['x'], $coordinates['y']));
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function setBottomLeftMarker()
    {
        $position    = 'bottom_left';
        $markerImage = clone $this->getMarkerImage();
        $this->resizeMarker(
            $markerImage,
            $this->getSchema()->getSchema()->markers->top->left->width,
            $this->getSchema()->getSchema()->markers->top->left->height,
            $position
        );

        $offsetY     = $this->getImage()->getImageHeight() * (1 - $this->heightSearchRation);
        $coordinates = $this->getMarkRecognition($markerImage, 0, $offsetY, $position);

        $this->setBottomLeft(new Point($coordinates['x'], $coordinates['y']));
    }

    /**
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function setBottomRightMarker()
    {
        $position    = 'bottom_right';
        $markerImage = clone $this->getMarkerImage();
        $this->resizeMarker(
            $markerImage,
            $this->getSchema()->getSchema()->markers->top->left->width,
            $this->getSchema()->getSchema()->markers->top->left->height,
            $position
        );

        $offsetX     = $this->getImage()->getImageWidth() * (1 - $this->widthSearchRation);
        $offsetY     = $this->getImage()->getImageHeight() * (1 - $this->heightSearchRation);
        $coordinates = $this->getMarkRecognition($markerImage, $offsetX, $offsetY, $position);

        $this->setBottomRight(new Point($coordinates['x'], $coordinates['y']));
    }

    /**
     * @param \Imagick $markerImage
     * @param float    $offsetX
     * @param float    $offsetY
     * @param string   $position
     *
     * @return int[]
     * @throws \ImagickDrawException
     * @throws \ImagickException
     */
    public function getMarkRecognition(Imagick $markerImage, float $offsetX, float $offsetY, string $position): array
    {
        $width  = $this->getImage()->getImageWidth() * $this->widthSearchRation;
        $height = $this->getImage()->getImageHeight() * $this->heightSearchRation;

        $region = $this->getImage()->getImageRegion($width, $height, $offsetX, $offsetY);
        $region->setImagePage($region->getImageWidth(), $region->getImageHeight(), 0, 0);
        $region->writeImage($this->getFolder() . '/' . 'region_' . $position . '.jpg');

        $offset     = [];
        $similarity = 0;
        $this->getLog()->debug('Searching ' . str_replace('_', '-', $position) . ' mark');
        $recognition = $region->subImageMatch($markerImage, $offset, $similarity);
        $this->getLog()->debug('Offset: ' . json_encode($offset));
        $this->getLog()->debug('Similarity: ' . json_encode($similarity));
        $recognition->writeImage($this->getFolder() . '/' . 'mark_recognition_' . $position . '.jpg');

        $x = $y = 0;
        if (!empty($offset)) {
            $x = round($offsetX + $offset['x']);
            $y = round($offsetY + $offset['y']);
        }

        $this->setRegionDraw();
        $this->getRegionDraw()->rectangle($offset['x'] - 10, $offset['y'] - 10, $offset['x'] + 10, $offset['y'] + 10);
        $this->getRegionDraw()->circle($offset['x'], $offset['y'], $offset['x'] + 2, $offset['y']);
        $region->drawImage($this->getRegionDraw());
        $region->writeImage($this->getFolder() . '/' . 'region_' . $position . '_drawn.jpg');

        $this->getDraw()->rectangle($x - 10, $y - 10, $x + 10, $y + 10);
        $this->getDraw()->circle($x, $y, $x + 2, $y);

        $this->getLog()->debug(mb_strtoupper(str_replace('_', ' ', $position)) . ': [' . $x . 'x' . $y . ']');

        return ['x' => $x, 'y' => $y];
    }

    /**
     * @param \Imagick $marker
     * @param float    $markerWidth
     * @param float    $markerHeight
     * @param string   $position
     *
     * @throws \ImagickException
     */
    private function resizeMarker(Imagick $marker, float $markerWidth, float $markerHeight, string $position)
    {
        $width  = round($this->getDpmX() * $markerWidth);
        $height = round($this->getDpmY() * $markerHeight);

        $this->getLog()->debug(
            'Resizing ' . str_replace('_', ' ', $position) . ' marker to: [' . $width . 'x' . $height . ']'
        );
        $marker->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 0.9);
        $marker->setImagePage($width, $height, 0, 0);
        $marker->writeImage($this->getFolder() . '/' . 'marker_' . $position . '_resized.jpg');
    }

    /**
     * @throws \ImagickException
     */
    private function initializeMarkerImage()
    {
        $this->markerImage = new Imagick(storage_path('app/scanner/settings/marker.jpg'));
    }

    /**
     * @throws \ImagickException
     */
    private function initializeDpm()
    {
        $this->setDpmX($this->getImage()->getImageWidth() / $this->getSchema()->getSchema()->width);
        $this->setDpmY($this->getImage()->getImageHeight() / env('SVG_PAGE_HEIGHT_MM'));

        $this->getLog()->debug('DPM: [' . $this->getDpmX() . 'x' . $this->getDpmY() . ']');
    }

    /**
     * @return \App\Vendors\Scanner\Point\Point
     */
    public function getTopLeft(): Point
    {
        return $this->topLeft;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $topLeft
     */
    public function setTopLeft(Point $topLeft): void
    {
        $this->topLeft = $topLeft;
    }

    /**
     * @return \App\Vendors\Scanner\Point\Point
     */
    public function getTopRight(): Point
    {
        return $this->topRight;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $topRight
     */
    public function setTopRight(Point $topRight): void
    {
        $this->topRight = $topRight;
    }

    /**
     * @return \App\Vendors\Scanner\Point\Point
     */
    public function getBottomLeft(): Point
    {
        return $this->bottomLeft;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $bottomLeft
     */
    public function setBottomLeft(Point $bottomLeft): void
    {
        $this->bottomLeft = $bottomLeft;
    }

    /**
     * @return \App\Vendors\Scanner\Point\Point
     */
    public function getBottomRight(): Point
    {
        return $this->bottomRight;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $bottomRight
     */
    public function setBottomRight(Point $bottomRight): void
    {
        $this->bottomRight = $bottomRight;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage($image): void
    {
        $this->image = $image;
    }

    /**
     * @return \Imagick
     */
    public function getMarkerImage(): Imagick
    {
        return $this->markerImage;
    }

    /**
     * @param \Imagick $markerImage
     */
    public function setMarkerImage(Imagick $markerImage): void
    {
        $this->markerImage = $markerImage;
    }

    /**
     * @return string
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    /**
     * @param string $folder
     */
    public function setFolder(string $folder): void
    {
        $this->folder = $folder;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }

    /**
     * @return \App\Vendors\Scanner\Schema\Schema
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     */
    public function setSchema(Schema $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @throws \ImagickDrawException
     */
    public function setDraw(): void
    {
        $this->draw = new ImagickDraw();
        $this->draw->setFontSize(6);
        $this->draw->setFillOpacity(0.4);
        $this->draw->setStrokeWidth(1);
        $this->draw->setStrokeOpacity(1);
        $this->draw->setFillOpacity(1);
        $this->draw->setFillColor(new ImagickPixel('#00000000'));
        $this->draw->setStrokeColor(new ImagickPixel('#00CC00'));
    }

    /**
     * @return \ImagickDraw
     */
    public function getDraw(): ImagickDraw
    {
        return $this->draw;
    }

    /**
     * @throws \ImagickDrawException
     */
    public function setRegionDraw(): void
    {
        $this->regionDraw = new ImagickDraw();
        $this->regionDraw->setFontSize(6);
        $this->regionDraw->setFillOpacity(0.4);
        $this->regionDraw->setStrokeWidth(1);
        $this->regionDraw->setStrokeOpacity(1);
        $this->regionDraw->setFillOpacity(1);
        $this->regionDraw->setFillColor(new ImagickPixel('#00000000'));
        $this->regionDraw->setStrokeColor(new ImagickPixel('#00CC00'));
    }

    /**
     * @return \ImagickDraw
     */
    public function getRegionDraw(): ImagickDraw
    {
        return $this->regionDraw;
    }

    /**
     * @param float $dpm
     */
    private function setDpmX(float $dpm)
    {
        $this->dpmX = $dpm;
    }

    /**
     * @return float
     */
    private function getDpmX(): float
    {
        return $this->dpmX;
    }

    /**
     * @param float $dpm
     */
    private function setDpmY(float $dpm)
    {
        $this->dpmY = $dpm;
    }

    /**
     * @return float
     */
    private function getDpmY(): float
    {
        return $this->dpmY;
    }
}
