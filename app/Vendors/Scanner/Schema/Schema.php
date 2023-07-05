<?php

namespace App\Vendors\Scanner\Schema;

use App\Vendors\Scanner\Point\Point;
use DOMDocument;
use Illuminate\Support\Facades\Log;

class Schema
{
    /** @var \Psr\Log\LoggerInterface */
    protected \Psr\Log\LoggerInterface $log;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $topLeft;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $topRight;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $bottomLeft;

    /** @var \App\Vendors\Scanner\Point\Point */
    private Point $bottomRight;

    /** @var float */
    private float $width;

    /** @var float */
    private float $height;

    /** @var float */
    private float $areaAdjustment;

    /** @var float */
    private float $tolerance;

    /** @var mixed */
    private mixed $schema;

    /** @var mixed */
    private mixed $labels;

    /** @var mixed */
    private mixed $markers;

    /** @var string */
    private string $folder;

    /** @var array */
    private array $languages = [];

    /**
     * Schema constructor.
     *
     * @param mixed  $schema
     * @param string $folder
     */
    public function __construct(mixed $schema, string $folder)
    {
        $this->setFolder($folder);
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => config('scanner.log_mode'),
            ]
        );

        $this->setSchema($schema);
        $this->initialize();
    }

    /**
     * Initialize Schema parameters
     */
    public function initialize()
    {
        $this->getLog()->debug('SCHEMA: ' . json_encode($this->getSchema()));

        $this->setAreaAdjustment($this->getSchema()->area_adjustment_percentage);
        $this->setTolerance($this->getSchema()->tolerance);
        $this->setMarkers($this->getSchema()->markers);

        $this->setTopLeft(
            new Point($this->getSchema()->markers->top->left->x, $this->getSchema()->markers->top->left->y)
        );
        $this->setTopRight(
            new Point($this->getSchema()->markers->top->right->x, $this->getSchema()->markers->top->right->y)
        );
        $this->setBottomLeft(
            new Point($this->getSchema()->markers->bottom->left->x, $this->getSchema()->markers->bottom->left->y)
        );
        $this->setBottomRight(
            new Point($this->getSchema()->markers->bottom->right->x, $this->getSchema()->markers->bottom->right->y)
        );

        $width  = Point::getWidth($this->getTopLeft(), $this->getTopRight());
        $height = Point::getHeight($this->getTopLeft(), $this->getBottomLeft());
        $this->setWidth($width);
        $this->setHeight($height);
        $this->getLog()->debug('Schema dimensions: [' . $width . 'x' . $height . ']');

        $this->setLabels($this->getSchema()->labels);
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
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }

    /**
     * @return mixed
     */
    public function getSchema(): mixed
    {
        return $this->schema;
    }

    /**
     * @param mixed $schema
     */
    public function setSchema(mixed $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * @param float $width
     */
    public function setWidth(float $width): void
    {
        $this->width = $width;
    }

    /**
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }

    /**
     * @param float $height
     */
    public function setHeight(float $height): void
    {
        $this->height = $height;
    }

    /**
     * @return float
     */
    public function getAreaAdjustment(): float
    {
        return $this->areaAdjustment;
    }

    /**
     * @param float $areaAdjustment
     */
    public function setAreaAdjustment(float $areaAdjustment): void
    {
        $this->areaAdjustment = $areaAdjustment;
    }

    /**
     * @return float
     */
    public function getTolerance(): float
    {
        return $this->tolerance;
    }

    /**
     * @param float $tolerance
     */
    public function setTolerance(float $tolerance): void
    {
        $this->tolerance = $tolerance;
    }

    /**
     * @return mixed
     */
    public function getLabels(): mixed
    {
        return $this->labels;
    }

    /**
     * @param mixed $labels
     */
    public function setLabels(mixed $labels): void
    {
        $this->labels = $labels;
    }

    /**
     * @param mixed $svgContent
     * @param array $languages
     * @param int   $page
     *
     * @return mixed
     */
    public static function read(mixed $svgContent, array $languages, int $page = 1): mixed
    {
        $pageHeight  = config('scanner.svg.height');
        $deltaHeight = ($page - 1) * $pageHeight;
        $minHeight   = ($page - 1) * $pageHeight;
        $maxHeight   = $page * $pageHeight;
        $document    = new DOMDocument();
        $document->loadXML($svgContent);

        $schema = [
            'area_adjustment_percentage' => config('scanner.area_adjustment'),
            'tolerance'                  => config('scanner.checkbox_tolerance'),
            'languages'                  => $languages ?? ['eng'],
        ];

        $svg              = $document->getElementsByTagName('svg');
        $schema['width']  = self::getAttribute($svg[0], 'width', true);
        $schema['height'] = self::getAttribute($svg[0], 'height', true);

        $rectangles = $document->getElementsByTagName('rect');
        foreach ($rectangles as $rectangle) {
            $y = self::getAttribute($rectangle, 'y', true);

            if ($y < $minHeight || $y > $maxHeight) {
                continue;
            }

            if (self::getAttribute($rectangle, 'data-type') === 'marker') {
                $markPosition = self::getAttribute($rectangle, 'data-type-mark');
                $attributes   = [
                    'x'      => self::getAttribute($rectangle, 'x', true),
                    'y'      => round(self::getAttribute($rectangle, 'y', true) - $deltaHeight, 2),
                    'width'  => self::getAttribute($rectangle, 'width', true),
                    'height' => self::getAttribute($rectangle, 'height', true),
                ];

                switch ($markPosition) {
                    case 'top-left':
                        $schema['markers']['top']['left'] = $attributes;
                        break;
                    case 'top-right':
                        $schema['markers']['top']['right'] = $attributes;
                        break;
                    case 'bottom-left':
                        $schema['markers']['bottom']['left'] = $attributes;
                        break;
                    case 'bottom-right':
                        $schema['markers']['bottom']['right'] = $attributes;
                        break;
                }

                continue;
            }

            if (!empty($rectangle->getAttribute('data-type'))) {
                $schema['labels'][] = [
                    'x'                  => self::getAttribute($rectangle, 'x', true),
                    'y'                  => round(self::getAttribute($rectangle, 'y', true) - $deltaHeight, 2),
                    'width'              => self::getAttribute($rectangle, 'width', true),
                    'height'             => self::getAttribute($rectangle, 'height', true),
                    'label'               => self::getAttribute($rectangle, 'data-label'),
                    'type'               => self::getAttribute($rectangle, 'data-type'),
                    'category_id'        => self::getAttribute($rectangle, 'data-category-id'),
                    'parent_category_id' => self::getAttribute($rectangle, 'data-parent-category-id'),
                ];
            }
        }

        return json_decode(json_encode($schema));
    }

    /**
     * @param mixed  $object
     * @param string $name
     * @param bool   $float
     *
     * @return float|string
     */
    private static function getAttribute(mixed $object, string $name, bool $float = false): float|string
    {
        $data = $object->getAttribute($name);

        if ($float) {
            $data = round(str_replace('mm', '', $data), 2);
        }

        return $data;
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
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param array $languages
     */
    public function setLanguages(array $languages): void
    {
        $this->languages = $languages;
    }

    /**
     * @return mixed
     */
    public function getMarkers(): mixed
    {
        return $this->markers;
    }

    /**
     * @param mixed $markers
     */
    public function setMarkers(mixed $markers): void
    {
        $this->markers = $markers;
    }
}
