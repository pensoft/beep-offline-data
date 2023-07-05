<?php


namespace App\Vendors\Scanner\Converter;


use App\Vendors\Scanner\Config\Config;
use App\Vendors\Scanner\Schema\Schema;
use Illuminate\Support\Facades\Log;

class Dpm
{
    /** @var Config */
    private Config $config;

    /** @var \App\Vendors\Scanner\Schema\Schema */
    private Schema $schema;

    /** @var float */
    private float $dpmX;

    /** @var float */
    private float $dpmY;

    /** @var float */
    private float $dpmTop;

    /** @var float */
    private float $dpmBottom;

    /** @var float */
    private float $dpmLeft;

    /** @var float */
    private float $dpmRight;

    /** @var \Psr\Log\LoggerInterface */
    protected \Psr\Log\LoggerInterface $log;

    public function __construct(Schema $schema, Config $config, string $folder)
    {
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => config('scanner.log_mode'),
            ]
        );

        $this->setSchema($schema);
        $this->setConfig($config);
        $this->calculateDpm();
    }

    /**
     * Calculate DPM by X from average width
     * Calculate DPM by Y from average height
     */
    public function calculateDpm()
    {
        $this->getLog()->debug(
            'DPM - Schema dimensions: [' . $this->getSchema()->getWidth() . 'x' . $this->getSchema()->getHeight() . ']'
        );

        $dpmX = round($this->getConfig()->getWidth() / $this->getSchema()->getWidth(), 6);
        $this->setDpmX($dpmX);

        $dpmY = round($this->getConfig()->getHeight() / $this->getSchema()->getHeight(), 6);
        $this->setDpmY($dpmY);

        $this->getLog()->debug('DPM: [' . $dpmX . 'x' . $dpmY . ']');

        $this->getLog()->debug('TOP WIDTH: ' . $this->getConfig()->getTopWidth());
        $this->getLog()->debug('BOTTOM WIDTH: ' . $this->getConfig()->getBottomWidth());
        $this->getLog()->debug('LEFT HEIGHT: ' . $this->getConfig()->getLeftHeight());
        $this->getLog()->debug('RIGHT HEIGHT: ' . $this->getConfig()->getRightHeight());

        $dpm = round($this->getConfig()->getTopWidth() / $this->getSchema()->getWidth(), 6);
        $this->setDpmTop($dpm);
        $this->getLog()->debug('DPM TOP: ' . $dpm);

        $dpm = round($this->getConfig()->getBottomWidth() / $this->getSchema()->getWidth(), 6);
        $this->setDpmBottom($dpm);
        $this->getLog()->debug('DPM BOTTOM: ' . $dpm);

        $dpm = round($this->getConfig()->getLeftHeight() / $this->getSchema()->getHeight(), 6);
        $this->setDpmLeft($dpm);
        $this->getLog()->debug('DPM LEFT: ' . $dpm);

        $dpm = round($this->getConfig()->getRightHeight() / $this->getSchema()->getHeight(), 6);
        $this->setDpmRight($dpm);
        $this->getLog()->debug('DPM RIGHT: ' . $dpm);
    }

    /**
     * @return \App\Vendors\Scanner\Config\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param \App\Vendors\Scanner\Config\Config $config
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
     * @return float
     */
    public function getDpmX(): float
    {
        return $this->dpmX;
    }

    /**
     * @param float $dpmX
     */
    public function setDpmX(float $dpmX): void
    {
        $this->dpmX = $dpmX;
    }

    /**
     * @return float
     */
    public function getDpmY(): float
    {
        return $this->dpmY;
    }

    /**
     * @param float $dpmY
     */
    public function setDpmY(float $dpmY): void
    {
        $this->dpmY = $dpmY;
    }

    /**
     * @return float
     */
    public function getDpmTop(): float
    {
        return $this->dpmTop;
    }

    /**
     * @param float $dpmTop
     */
    public function setDpmTop(float $dpmTop): void
    {
        $this->dpmTop = $dpmTop;
    }

    /**
     * @return float
     */
    public function getDpmBottom(): float
    {
        return $this->dpmBottom;
    }

    /**
     * @param float $dpmBottom
     */
    public function setDpmBottom(float $dpmBottom): void
    {
        $this->dpmBottom = $dpmBottom;
    }

    /**
     * @return float
     */
    public function getDpmLeft(): float
    {
        return $this->dpmLeft;
    }

    /**
     * @param float $dpmLeft
     */
    public function setDpmLeft(float $dpmLeft): void
    {
        $this->dpmLeft = $dpmLeft;
    }

    /**
     * @return float
     */
    public function getDpmRight(): float
    {
        return $this->dpmRight;
    }

    /**
     * @param float $dpmRight
     */
    public function setDpmRight(float $dpmRight): void
    {
        $this->dpmRight = $dpmRight;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }
}
