<?php

namespace App\Support;

class SizeFormatter
{
    public static function formatBytes(int|float|null $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes < 1) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = 1024; // divider to B > KB > MB > GB
        $power = (int) floor(log($bytes, $base));
        $power = min($power, count($units) - 1);

        return number_format($bytes / ($base ** $power), $precision).' '.$units[$power];
    }
}
