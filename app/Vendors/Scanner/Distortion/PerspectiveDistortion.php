<?php

namespace App\Vendors\Scanner\Distortion;

use App\Vendors\Scanner\Config\Config;
use Illuminate\Support\Facades\Log;
use Imagick;

class PerspectiveDistortion
{
    /** @var \Psr\Log\LoggerInterface */
    protected \Psr\Log\LoggerInterface $log;

    /** @var \Imagick */
    private Imagick $image;

    /** @var \App\Vendors\Scanner\Config\Config */
    private Config $config;

    /** @var string */
    private string $folder;

    private array $coordinates;

    /**
     * PerspectiveDistortion constructor.
     *
     * @param \Imagick                           $imagick
     * @param \App\Vendors\Scanner\Config\Config $config
     * @param string                             $folder
     */
    public function __construct(Imagick $imagick, Config $config, string $folder)
    {
        $this->log = Log::build(
            [
                'driver' => 'single',
                'path'   => $folder . '/' . 'scan.log',
                'level'  => env('SCANNER_LOG_MODE', 'debug'),
            ]
        );
        $this->setImage($imagick);
        $this->setConfig($config);
        $this->setFolder($folder);
        $this->initializeCoordinates();
    }

    /**
     * @return \Imagick
     * @throws \ImagickException
     */
    public function distort(): Imagick
    {
        $this->getImage()->distortImage(Imagick::DISTORTION_PERSPECTIVE, $this->getCoordinates(), true);
        $this->getImage()->writeImage($this->getFolder() . '/' . 'distorted.jpg');

        $this->setImage(new Imagick($this->getFolder() . '/' . 'distorted.jpg'));

        return $this->getImage();
    }

    /**
     * Initialize coordinates
     */
    public function initializeCoordinates()
    {
//        array_merge($this->getCoordinates(), $this->getTopLeftCoordinates());
//        array_merge($this->getCoordinates(), $this->getTopRightCoordinates());
//        array_merge($this->getCoordinates(), $this->getBottomLeftCoordinates());
//        array_merge($this->getCoordinates(), $this->getBottomRightCoordinates());

        $coordinates = $this->getTopLeftCoordinates();
        $coordinates = array_merge($coordinates, $this->getTopRightCoordinates());
        $coordinates = array_merge($coordinates, $this->getBottomLeftCoordinates());
        $coordinates = array_merge($coordinates, $this->getBottomRightCoordinates());

        $this->setCoordinates($coordinates);
    }

    /**
     * @return array
     */
    private function getTopLeftCoordinates(): array
    {
        return [
            $this->getConfig()->getTopLeft()->getX(),
            $this->getConfig()->getTopLeft()->getY(),
            $this->getConfig()->getTopLeft()->getX(),
            $this->getConfig()->getTopLeft()->getY(),
        ];
    }

    /**
     * @return array
     */
    private function getTopRightCoordinates(): array
    {
        return [
            $this->getConfig()->getTopRight()->getX(),
            $this->getConfig()->getTopRight()->getY(),
            $this->getConfig()->getTopLeft()->getX() + $this->getConfig()->getWidth(),
            $this->getConfig()->getTopLeft()->getY(),
        ];
    }

    /**
     * @return array
     */
    private function getBottomLeftCoordinates(): array
    {
        return [
            $this->getConfig()->getBottomLeft()->getX(),
            $this->getConfig()->getBottomLeft()->getY(),
            $this->getConfig()->getTopLeft()->getX(),
            $this->getConfig()->getTopLeft()->getY() + $this->getConfig()->getHeight(),
        ];
    }

    /**
     * @return array
     */
    private function getBottomRightCoordinates(): array
    {
        return [
            $this->getConfig()->getBottomRight()->getX(),
            $this->getConfig()->getBottomRight()->getY(),
            $this->getConfig()->getTopLeft()->getX() + $this->getConfig()->getWidth(),
            $this->getConfig()->getTopLeft()->getY() + $this->getConfig()->getHeight(),
        ];
    }

    /**
     * @return \Imagick
     */
    public function getImage(): Imagick
    {
        return $this->image;
    }

    /**
     * @param \Imagick $imagick
     */
    public function setImage(Imagick $imagick): void
    {
        $this->image = $imagick;
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
    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    /**
     * @param array $coordinates
     */
    public function setCoordinates(array $coordinates): void
    {
        $this->coordinates = $coordinates;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    public function getLog(): \Psr\Log\LoggerInterface
    {
        return $this->log;
    }
}
