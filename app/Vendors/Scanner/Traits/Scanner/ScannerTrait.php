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
        preg_match('/data:image\/(.*)\;base64,/', $blob, $matches);

        $extension = $matches[1] ?? null;

        if (mb_strtolower($extension) === 'jpeg') {
            $extension = mb_strtoupper('jpg');
        }

        if ($lowerCase) {
            $extension = mb_strtolower($extension);
        }

        return $extension;
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
}
