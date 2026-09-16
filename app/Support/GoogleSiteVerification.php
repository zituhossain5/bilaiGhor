<?php

namespace App\Support;

class GoogleSiteVerification
{
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/content\s*=\s*(["\'])(.*?)\1/i', $value, $matches)) {
            $value = $matches[2];
        }

        $value = preg_replace('/^google-site-verification\s*=\s*/i', '', trim($value));
        $value = trim((string) $value, " \t\n\r\0\x0B\"'");

        return preg_match('/^[A-Za-z0-9_-]+$/D', $value) ? $value : null;
    }
}
