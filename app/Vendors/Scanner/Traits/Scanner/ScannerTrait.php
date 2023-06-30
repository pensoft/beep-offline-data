<?php

namespace App\Vendors\Scanner\Traits\Scanner;

use Carbon\Carbon;

trait ScannerTrait
{
    /**
     * @param      $blob
     * @param bool $lowerCase
     *
     * @return string|null
     */
    public function getExtensionFromBlob($blob, bool $lowerCase = false): ?string
    {
        preg_match('/data:image\/(.*)\;base64,/', substr($blob, 0, 100), $matches);

        $extension = $matches[1] ?? null;

        return $lowerCase ? mb_strtolower($extension) : $extension;
    }

    /**
     * @param $blob
     *
     * @return false|string
     */
    public function getBlobContents($blob): bool|string
    {
        $contents = str_replace('data:image/' . $this->getExtensionFromBlob($blob) . ';base64,', '', $blob);

        return base64_decode($contents);
    }

    /**
     * @param string         $basePath
     * @param string         $ip
     * @param \Carbon\Carbon $date
     *
     * @return string
     */
    public function getDirectoryPath(string $basePath, string $ip, Carbon $date): string
    {
        $basePath .= '/' . $date->format('Y/m/d') . '/';

        return $basePath . str_replace('.', '.', $ip) . '_' . $date->format('His');
    }

    /**
     * @param string $label
     *
     * @return string
     */
    public function getLabelKey(string $label)
    {
        // remove spaces
        $label = trim($label);

        // to lowercase
        $label = mb_strtolower($label);

        // return hashed value
        return md5($label);
    }
}
