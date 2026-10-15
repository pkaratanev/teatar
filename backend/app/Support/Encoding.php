<?php

namespace App\Support;

/**
 * Several theatre sites declare windows-1251 but some servers answer in UTF-8
 * depending on the client. Decide from the bytes, not from the header, and
 * make the <meta charset> agree — otherwise the HTML parser converts again.
 */
final class Encoding
{
    public static function toUtf8(string $body): string
    {
        if (! mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'Windows-1251');
        }

        return preg_replace('/(<meta[^>]+charset=["\']?)[\w-]+/i', '${1}utf-8', $body) ?? $body;
    }
}
