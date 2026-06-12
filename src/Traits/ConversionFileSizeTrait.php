<?php

namespace App\Traits;

trait ConversionFileSizeTrait
{
    public function convertFileSize(int $bytes): string
    {
        $units = ['Octets', 'Ko', 'Mo', 'Go'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return number_format($bytes, 3, ',', ' ') . ' ' . $units[$i];
    }
}
