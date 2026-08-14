<?php

/*
|--------------------------------------------------------------------------
| Lightweight mb_substr fallback
|--------------------------------------------------------------------------
|
| Beberapa konfigurasi shared hosting tidak memuat ext-mbstring untuk PHP
| web. Symfony memang menyediakan polyfill, tetapi fallback mb_substr-nya
| menggunakan panjang 2147483647 ketika argumen panjang tidak diberikan.
| Pada build iconv tertentu operasi itu dapat sangat lambat. Definisi ini
| dimuat sebelum Composer dan memakai panjang string yang sebenarnya.
|
*/

if (! function_exists('mb_substr')) {
    function mb_substr(?string $string, ?int $start, ?int $length = null, ?string $encoding = null): string
    {
        $string = (string) $string;
        $start = (int) $start;
        $encoding = $encoding ?: 'UTF-8';

        if (function_exists('iconv_substr')) {
            if ($length === null) {
                $characterCount = function_exists('iconv_strlen')
                    ? @iconv_strlen($string, $encoding)
                    : false;
                $length = $characterCount === false ? strlen($string) : max(0, $characterCount);
            }

            $result = @iconv_substr($string, $start, $length, $encoding);

            if ($result !== false) {
                return $result;
            }
        }

        return $length === null
            ? (string) substr($string, $start)
            : (string) substr($string, $start, $length);
    }
}
