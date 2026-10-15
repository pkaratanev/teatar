<?php

namespace App\Support;

/**
 * Several theatre sites declare windows-1251 but some servers answer in UTF-8
 * depending on the client. Decide from the bytes, not from the header.
 */
final class Encoding
{
    public static function toUtf8(string $body): string
    {
        if (mb_check_encoding($body, 'UTF-8')) {
            return $body;
        }

        return mb_convert_encoding($body, 'UTF-8', 'Windows-1251');
    }
}
