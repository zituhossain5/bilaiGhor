<?php

namespace App\Support;

/**
 * JSON-LD output for <script type="application/ld+json">.
 * Empty values (null, '', []) are dropped so Google never sees blank properties;
 * Bangla stays readable (no \u escapes) and "<" / ">" are escaped so text can't close the script tag.
 */
class JsonLd
{
    public static function encode(array $data): string
    {
        return json_encode(
            self::clean($data),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG
        ) ?: '{}';
    }

    /** Absolute URL for a stored file path (e.g. "public/uploads/x 1.webp"), with unsafe characters encoded. */
    public static function fileUrl(?string $path): ?string
    {
        if (!filled($path)) {
            return null;
        }

        return asset(implode('/', array_map('rawurlencode', explode('/', ltrim($path, '/')))));
    }

    private static function clean(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = self::clean($value);
            }
            if ($value === null || $value === '' || $value === []) {
                unset($data[$key]);
                continue;
            }
            $data[$key] = $value;
        }

        return array_is_list($data) ? array_values($data) : $data;
    }
}
