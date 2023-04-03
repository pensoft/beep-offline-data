<?php

namespace App\Vendors\Scanner\Config;

use App\Vendors\Scanner\Point\Point;
use Illuminate\Support\Facades\Log;

class Config
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
    private float $topWidth;

    /** @var float */
    private float $bottomWidth;

    /** @var float */
    private float $height;

    /** @var float */
    private float $leftHeight;

    /** @var float */
    private float $rightHeight;

    /** @var mixed */
    private mixed $config;

    /** @var float */
    private float $offsetX = 0;

    /** @var float */
    private float $offsetY = 0;

    /** @var float */
    private float $offsetTop = 0;

    /** @var float */
    private float $offsetBottom = 0;

    /** @var float */
    private float $offsetLeft = 0;

    /** @var float */
    private float $offsetRight = 0;

    /** @var string */
    private string $folder;

    /**
     * Config constructor.
     *
     * @param mixed  $config
     * @param string $folder
     */
    public function __construct(mixed $config, string $folder)
    {
        $this->setFolder($folder);
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => env('SCANNER_LOG_MODE', 'debug'),
            ]
        );

        $this->setConfig($config);
        $this->initialize();
    }

    /**
     * Initialize Config parameters
     */
    public function initialize()
    {
        $this->log->debug('CONFIG: ' . json_encode($this->getConfig()));

        $point = new Point($this->getConfig()->markers->top->left->x, $this->getConfig()->markers->top->left->y);
        $this->setTopLeft($point);

        $point = new Point($this->getConfig()->markers->top->right->x, $this->getConfig()->markers->top->right->y);
        $this->setTopRight($point);

        $point = new Point($this->getConfig()->markers->bottom->left->x, $this->getConfig()->markers->bottom->left->y);
        $this->setBottomLeft($point);

        $point = new Point(
            $this->getConfig()->markers->bottom->right->x,
            $this->getConfig()->markers->bottom->right->y
        );
        $this->setBottomRight($point);

        $this->setTopWidth(Point::getWidth($this->getTopLeft(), $this->getTopRight()));
        $this->setBottomWidth(Point::getWidth($this->getBottomLeft(), $this->getBottomRight()));

        $width = round(($this->getTopWidth() + $this->getBottomWidth()) / 2);
        $this->setWidth($width);

        $this->setLeftHeight(Point::getHeight($this->getTopLeft(), $this->getBottomLeft()));
        $this->setRightHeight(Point::getHeight($this->getTopRight(), $this->getBottomRight()));

        $height = round(($this->getLeftHeight() + $this->getRightHeight()) / 2);
        $this->setHeight($height);
        $this->getLog()->debug('AVG dimensions: [' . $width . 'x' . $height . ']');

        $offset = round($this->getTopRight()->getY() - $this->getTopLeft()->getY());
        $this->setOffsetTop($offset);
        $this->getLog()->debug('OFFSET TOP: ' . $offset);

        $offset = round($this->getBottomRight()->getY() - $this->getBottomLeft()->getY());
        $this->setOffsetBottom($offset);
        $this->getLog()->debug('OFFSET BOTTOM: ' . $offset);

        $offset = round($this->getBottomLeft()->getX() - $this->getTopLeft()->getX());
        $this->setOffsetLeft($offset);
        $this->getLog()->debug('OFFSET LEFT: ' . $offset);

        $offset = round($this->getBottomRight()->getX() - $this->getTopRight()->getX());
        $this->setOffsetRight($offset);
        $this->getLog()->debug('OFFSET RIGHT: ' . $offset);

        $offsetX = round($this->getBottomLeft()->getX() - $this->getTopLeft()->getX());
        $this->setOffsetX($offsetX);

        $offsetY = round($this->getBottomRight()->getY() - $this->getBottomLeft()->getY());
        $this->setOffsetY($offsetY);

        $this->getLog()->debug('Config offset: [' . $offsetX . 'x' . $offsetY . ']');
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
    public function getConfig(): mixed
    {
        return $this->config;
    }

    /**
     * @param mixed $config
     */
    public function setConfig(mixed $config): void
    {
        $this->config = $config;
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
    public function getTopWidth(): float
    {
        return $this->topWidth;
    }

    /**
     * @param float $topWidth
     */
    public function setTopWidth(float $topWidth): void
    {
        $this->topWidth = $topWidth;
    }

    /**
     * @return float
     */
    public function getBottomWidth(): float
    {
        return $this->bottomWidth;
    }

    /**
     * @param float $bottomWidth
     */
    public function setBottomWidth(float $bottomWidth): void
    {
        $this->bottomWidth = $bottomWidth;
    }

    /**
     * @return float
     */
    public function getLeftHeight(): float
    {
        return $this->leftHeight;
    }

    /**
     * @param float $leftHeight
     */
    public function setLeftHeight(float $leftHeight): void
    {
        $this->leftHeight = $leftHeight;
    }

    /**
     * @return float
     */
    public function getRightHeight(): float
    {
        return $this->rightHeight;
    }

    /**
     * @param float $rightHeight
     */
    public function setRightHeight(float $rightHeight): void
    {
        $this->rightHeight = $rightHeight;
    }

    /**
     * @return float
     */
    public function getOffsetX(): float
    {
        return $this->offsetX;
    }

    /**
     * @param float $offsetX
     */
    public function setOffsetX(float $offsetX): void
    {
        $this->offsetX = $offsetX;
    }

    /**
     * @return float
     */
    public function getOffsetY(): float
    {
        return $this->offsetY;
    }

    /**
     * @param float $offsetY
     */
    public function setOffsetY(float $offsetY): void
    {
        $this->offsetY = $offsetY;
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
     * @return float
     */
    public function getOffsetTop(): float
    {
        return $this->offsetTop;
    }

    /**
     * @param float $offsetTop
     */
    public function setOffsetTop(float $offsetTop): void
    {
        $this->offsetTop = $offsetTop;
    }

    /**
     * @return float
     */
    public function getOffsetBottom(): float
    {
        return $this->offsetBottom;
    }

    /**
     * @param float $offsetBottom
     */
    public function setOffsetBottom(float $offsetBottom): void
    {
        $this->offsetBottom = $offsetBottom;
    }

    /**
     * @return float
     */
    public function getOffsetLeft(): float
    {
        return $this->offsetLeft;
    }

    /**
     * @param float $offsetLeft
     */
    public function setOffsetLeft(float $offsetLeft): void
    {
        $this->offsetLeft = $offsetLeft;
    }

    /**
     * @return float
     */
    public function getOffsetRight(): float
    {
        return $this->offsetRight;
    }

    /**
     * @param float $offsetRight
     */
    public function setOffsetRight(float $offsetRight): void
    {
        $this->offsetRight = $offsetRight;
    }
}
