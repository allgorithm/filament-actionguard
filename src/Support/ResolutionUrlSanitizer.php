<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Support;

final class ResolutionUrlSanitizer
{
    public static function sanitize(mixed $url): ?string
    {
        if (! is_string($url)) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x20\x7F\\\\]/', $url) === 1 || str_starts_with($url, '//')) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $allowedSchemes = ['https'];
        if (config('filament-actionguard.allow_insecure_resolution_urls', false)) {
            $allowedSchemes[] = 'http';
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), $allowedSchemes, true) ? $url : null;
    }
}
