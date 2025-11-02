<?php

namespace App\Service;

final class CodeGenerator
{
    private const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Deterministic short code from a canonical URL.
     */
    public function generate(string $canonicalUrl, int $length = 7): string
    {
        $base62 = $this->toBase62(hexdec(substr(hash('sha256', $canonicalUrl), 0, 15)));

        if (strlen($base62) < $length) {
            $base62 = str_repeat(self::ALPHABET[0], $length - strlen($base62)) . $base62;
        }

        return substr($base62, 0, $length);
    }

    private function toBase62(int $num): string
    {
        $alphabet = self::ALPHABET;
        if ($num === 0) {
            return $alphabet[0];
        }

        $out = '';
        while ($num > 0) {
            $rem = $num % 62;
            $out = $alphabet[$rem] . $out;
            $num = intdiv($num, 62);
        }

        return $out;
    }
}
