<?php

namespace App\Support;

/** "/storage/shows/a.jpg" on https://nationaltheatre.bg → "https://nationaltheatre.bg/storage/shows/a.jpg". */
final class AbsoluteUrl
{
    public static function from(?string $src, string $base): ?string
    {
        $src = trim((string) $src);
        if ($src === '' || str_starts_with($src, 'data:')) {
            return null;
        }
        if (preg_match('~^https?://~i', $src)) {
            return $src;
        }
        if (str_starts_with($src, '//')) {
            return 'https:'.$src;
        }

        $origin = preg_match('~^(https?://[^/]+)~i', $base, $m) ? $m[1] : rtrim($base, '/');

        return $origin.'/'.ltrim($src, '/');
    }
}
