<?php

namespace App\Vendors\Scanner\Label;

use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Converter\Dpm;
use App\Vendors\Scanner\Point\Point;
use App\Vendors\Scanner\Schema\Schema;
use App\Vendors\Scanner\Traits\Scanner\ScannerTrait;
use Illuminate\Support\Facades\Log;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class Label
{
    use ScannerTrait;

    /** @var \Imagick */
    private Imagick $image;

    /** @var \Psr\Log\LoggerInterface */
    protected \Psr\Log\LoggerInterface $log;

    /** @var \ImagickDraw */
    protected ImagickDraw $draw;

    /**
     * Pointer Top Left
     *
     * @var Point
     */
    private Point $pointA;

    /**
     * Pointer Bottom Right
     *
     * @var Point
     */
    private Point $pointB;

    /** @var string */
    protected string $category_id;

    /** @var string */
    protected string $parent_category_id;

    /** @var string */
    protected string $textLabel;

    /** @var string */
    protected string $type;

    /** @var string */
    protected string $value;

    /** @var string */
    protected string $blob;

    /** @var bool */
    protected bool $checked = false;

    /** @var Config */
    private Config $config;

    /** @var \App\Vendors\Scanner\Schema\Schema */
    private Schema $schema;

    /** @var Dpm */
    private Dpm $dpm;

    /** @var mixed */
    private mixed $labelData;

    /** @var string */
    private string $folder;

    /**
     * Label constructor.
     *
     * @param \Imagick                           $image
     * @param \App\Vendors\Scanner\Schema\Schema $schema
     * @param \App\Vendors\Scanner\Config\Config $config
     * @param \App\Vendors\Scanner\Converter\Dpm $dpm
     * @param mixed                              $labelData
     * @param string                             $folder
     *
     * @throws \ImagickDrawException
     */
    public function __construct(
        Imagick $image,
        Schema $schema,
        Config $config,
        Dpm $dpm,
        mixed $labelData,
        string $folder
    ) {
        $this->setFolder($folder);

        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => config('scanner.log_mode'),
            ]
        );
        $this->log->debug('LABEL DATA: ' . json_encode($labelData));

        $this->setImage($image);
        $this->setSchema($schema);
        $this->setConfig($config);
        $this->setDpm($dpm);
        $this->setLabelData($labelData);
        $this->initializeLabel();
        $this->setDraw();
    }

    /**
     * @return string[]
     */
    public function getResult(): array
    {
        return [
            'category_id'        => $this->getCategoryId(),
            'parent_category_id' => $this->getParentCategoryId(),
            'type'               => $this->getType(),
            'value'              => $this->getValue(),
            'image'              => $this->getBlob(),
        ];
    }

    /**
     * Initialize Label
     */
    public function initializeLabel()
    {
        $this->setCategoryId($this->getLabelData()->category_id ?? '');
        $this->getLog()->debug('Label category id: ' . $this->getCategoryId());

        $this->setParentCategoryId($this->getLabelData()->parent_category_id ?? '');
        $this->getLog()->debug('Label parent category id: ' . $this->getParentCategoryId());

        $this->setType($this->getLabelData()->type ?? '');
        $this->getLog()->debug('Label type: ' . $this->getType());

        $this->setTextLabel($this->getLabelData()->label ?? '');
        $this->getLog()->debug('Label text label: ' . $this->getTextLabel());

        $topLeft = $this->getSchema()->getTopLeft();

        $x = $this->getLabelData()->x - $topLeft->getX();
        $y = $this->getLabelData()->y - $topLeft->getY();
        $this->getLog()->debug('Point A: [' . $x . 'x' . $y . ']');

        $dpmX = $this->getDpm()->getDpmX();
        $dpmY = $this->getDpm()->getDpmY();
        $x    *= $dpmX;
        $y    *= $dpmY;
        $this->getLog()->debug('Point A x DPM: [' . $x . 'x' . $y . ']');

        $x += $this->getConfig()->getTopLeft()->getX();
        $y += $this->getConfig()->getTopLeft()->getY();
        $this->getLog()->debug('Point A position from 0x0: [' . $x . 'x' . $y . ']');

        $adjustmentX = $this->getAdjustmentX(true);
        $adjustmentY = $this->getAdjustmentY(true);
        $this->getLog()->debug('Area adjustment (px): [' . $adjustmentX . 'x' . $adjustmentY . ']');

        $x = round($x + $adjustmentX);
        $y = round($y + $adjustmentY);
        $this->getLog()->debug('Point A + area adjustment: [' . $x . 'x' . $y . ']');

        $this->setPointA(new Point($x, $y));

        $x = $this->getLabelData()->x + $this->getLabelData()->width - $topLeft->getX();
        $y = $this->getLabelData()->y + $this->getLabelData()->height - $topLeft->getY();
        $this->getLog()->debug('Point B: [' . $x . 'x' . $y . ']');

        $dpmX = $this->getDpm()->getDpmX();
        $dpmY = $this->getDpm()->getDpmY();
        $x    *= $dpmX;
        $y    *= $dpmY;
        $this->getLog()->debug('Point B x DPM: [' . $x . 'x' . $y . ']');

        $x += $this->getConfig()->getTopLeft()->getX();
        $y += $this->getConfig()->getTopLeft()->getY();
        $this->getLog()->debug('Point B position from 0x0: [' . $x . 'x' . $y . ']');

        $x = round($x - $adjustmentX);
        $y = round($y - $adjustmentY);
        $this->getLog()->debug('Point B + area adjustment: [' . $x . 'x' . $y . ']');

        $this->setPointB(new Point($x, $y));
    }

    /**
     * @param bool $inPixels
     *
     * @return int
     */
    public function getAdjustmentX(bool $inPixels = false): int
    {
        $x = 0;
        if (!empty($this->getSchema()->getAreaAdjustment())) {
            $x = $this->getLabelData()->width;

            $multiplier = $this->getSchema()->getAreaAdjustment() / 100;
            if ($this->getLabelData()->width / $this->getLabelData()->height > 1.5) {
                $adjustment = $this->getSchema()->getAreaAdjustment() +
                              (100 - $this->getSchema()->getAreaAdjustment()) / 2;
                $multiplier = $adjustment / 100;
            }
            $x -= ($this->getLabelData()->width * $multiplier);
            $x /= 2;
        }

        if ($inPixels) {
            return round($x * $this->getDpm()->getDpmX());
        }

        return $x;
    }

    /**
     * @param bool $inPixels
     *
     * @return int
     */
    public function getAdjustmentY(bool $inPixels = false): int
    {
        $y = 0;
        if (!empty($this->getSchema()->getAreaAdjustment())) {
            $y = $this->getLabelData()->height;
            $y -= ($this->getLabelData()->height * $this->getSchema()->getAreaAdjustment() / 100);
            $y /= 2;
        }

        if ($inPixels) {
            return round($y * $this->getDpm()->getDpmY());
        }

        return $y;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     *
     * @return float
     */
    public function getOffsetX(float $x, float $y, float $width, float $height): float
    {
        $delta = $y / $height;
        $this->getLog()->debug('DELTA X: ' . $delta);

        if ($x <= $width / 2) {
            $offset = $delta * $this->getConfig()->getOffsetLeft();
            $this->getLog()->debug('X is in left half, calculating with left offset: ' . $offset);

            return $offset;
        }

        $offset = $delta * $this->getConfig()->getOffsetRight();
        $this->getLog()->debug('X is in right half, calculating with right offset: ' . $offset);

        return $offset;
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     *
     * @return float
     */
    public function getOffsetY(float $x, float $y, float $width, float $height): float
    {
        $delta = $x / $width;
        $this->getLog()->debug('DELTA Y: ' . $delta);

        if ($y <= $height / 2) {
            $offset = $delta * $this->getConfig()->getOffsetTop();
            $this->getLog()->debug('Y is in top half, calculating with top offset: ' . $offset);

            return $offset;
        }

        $offset = $delta * $this->getConfig()->getOffsetBottom();
        $this->getLog()->debug('Y is in bottom half, calculating with bottom offset: ' . $offset);

        return $offset;
    }

    /**
     * @param float $x
     * @param float $y
     *
     * @return float
     */
    public function getLinearDmpX(float $x, float $y): float
    {
        if ($y <= $this->getSchema()->getHeight() / 2) {
            $this->getLog()->debug('Y is in top half, getting DPM top: ' . $this->getDpm()->getDpmTop());

            return $this->getDpm()->getDpmTop();
        }

        $this->getLog()->debug('Y is in bottom half, getting DPM bottom: ' . $this->getDpm()->getDpmBottom());

        return $this->getDpm()->getDpmBottom();
    }

    /**
     * @param float $x
     * @param float $y
     *
     * @return float
     */
    public function getLinearDmpY(float $x, float $y): float
    {
        if ($x < $this->getSchema()->getWidth() / 2) {
            $this->getLog()->debug('X is in left half, getting DPM left: ' . $this->getDpm()->getDpmLeft());

            return $this->getDpm()->getDpmLeft();
        }

        $this->getLog()->debug('X is in right half, getting DPM right: ' . $this->getDpm()->getDpmRight());

        return $this->getDpm()->getDpmRight();
    }

    /**
     * @return \Imagick
     * @throws \ImagickException
     */
    public function getLabelImage(): Imagick
    {
        $x = $this->getPointA()->getX();
        $y = $this->getPointA()->getY();
        $this->getLog()->debug('Image region top left: [' . $x . 'x' . $y . ']');
        $width  = $this->getPointB()->getX() - $this->getPointA()->getX();
        $height = $this->getPointB()->getY() - $this->getPointA()->getY();
        $this->getLog()->debug('Image region dimensions: [' . $width . 'x' . $height . ']');

        return $this->getImage()
                    ->getImageRegion($width, $height, $this->getPointA()->getX(), $this->getPointA()->getY());
    }

    /**
     * @param string $color
     *
     * @throws \ImagickDrawException
     * @throws \ImagickException
     * @throws \ImagickPixelException
     */
    public function markLabelImage(string $color): void
    {
        $this->getDraw()->setStrokeColor(new ImagickPixel($color));

        $this->getDraw()->rectangle(
            $this->getPointA()->getX(),
            $this->getPointA()->getY(),
            $this->getPointB()->getX(),
            $this->getPointB()->getY());

        $this->getImage()->drawImage($this->getDraw());
    }

    /**
     * @return \Imagick
     */
    public function getImage(): Imagick
    {
        return $this->image;
    }

    /**
     * @param \Imagick $image
     */
    public function setImage(Imagick $image): void
    {
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getCategoryId(): string
    {
        return $this->category_id;
    }

    /**
     * @param string $category_id
     */
    public function setCategoryId(string $category_id): void
    {
        $this->category_id = $category_id;
    }

    /**
     * @return \App\Vendors\Scanner\Point\Point
     */
    public function getPointA(): Point
    {
        return $this->pointA;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $pointA
     */
    public function setPointA(Point $pointA): void
    {
        $this->pointA = $pointA;
    }

    /**
     * @return \App\Vendors\Scanner\Point\Point
     */
    public function getPointB(): Point
    {
        return $this->pointB;
    }

    /**
     * @param \App\Vendors\Scanner\Point\Point $pointB
     */
    public function setPointB(Point $pointB): void
    {
        $this->pointB = $pointB;
    }

    /**
     * @return string
     */
    public function getParentCategoryId(): string
    {
        return $this->parent_category_id;
    }

    /**
     * @param string $parent_category_id
     */
    public function setParentCategoryId(string $parent_category_id): void
    {
        $this->parent_category_id = $parent_category_id;
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
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function isChecked(): bool
    {
        return $this->checked;
    }

    /**
     * @param bool $checked
     */
    public function setChecked(bool $checked): void
    {
        $this->checked = $checked;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }

    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param Config $config
     */
    public function setConfig(Config $config): void
    {
        $this->config = $config;
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
     * @return \ImagickDraw
     */
    public function getDraw(): ImagickDraw
    {
        return $this->draw;
    }

    /**
     * Set Imagick Draw
     *
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
    }

    /**
     * @return mixed
     */
    public function getLabelData(): mixed
    {
        return $this->labelData;
    }

    /**
     * @param mixed $labelData
     */
    public function setLabelData(mixed $labelData): void
    {
        $this->labelData = $labelData;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getBlob(): string
    {
        return $this->blob;
    }

    /**
     * @param string $blob
     */
    public function setBlob(string $blob): void
    {
        $this->blob = $blob;
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
     * @return \App\Vendors\Scanner\Converter\Dpm
     */
    public function getDpm(): Dpm
    {
        return $this->dpm;
    }

    /**
     * @param \App\Vendors\Scanner\Converter\Dpm $dpm
     */
    public function setDpm(Dpm $dpm): void
    {
        $this->dpm = $dpm;
    }

    /**
     * @return string
     */
    public function getTextLabel(): string
    {
        return $this->textLabel;
    }

    /**
     * @param string $textLabel
     */
    public function setTextLabel(string $textLabel): void
    {
        $this->textLabel = $textLabel;
    }
}
