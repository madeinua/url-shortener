<?php

namespace App\Service;

use InvalidArgumentException;

final class UrlCanonicalizer
{
    public function canonicalize(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            throw new InvalidArgumentException('Empty URL');
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Invalid URL');
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only http/https are allowed');
        }

        $host = strtolower($parts['host']);
        $port = $parts['port'] ?? null;

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        $path = $parts['path'] ?? '/';
        if ($path === '') {
            $path = '/';
        }

        // Ensure path starts with a slash
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        // Remove duplicate slashes
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        $query = '';
        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str($parts['query'], $q);
            ksort($q);
            $query = http_build_query($q, '', '&', PHP_QUERY_RFC3986);
        }

        return $scheme . '://' . ($host . ($port ? ':' . $port : '')) . $path . ($query !== '' ? '?' . $query : '');
    }
}
