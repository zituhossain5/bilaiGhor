<?php

namespace App\Support;

use App\Models\Product;

class ProductVideo
{
    public static function youtubeId(?string $value): ?string
    {
        $value = trim((string) $value);
        if (preg_match('/^[A-Za-z0-9_-]{11}$/D', $value)) {
            return $value;
        }

        if (preg_match('~^(?:(?:www|m)\.)?(?:youtube\.com|youtu\.be)/~i', $value)) {
            $value = 'https://'.$value;
        }

        $parts = parse_url($value);
        if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');
        $id = null;
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^(?:shorts|embed|v)/([^/]+)~', $path, $matches)) {
                $id = $matches[1];
            }
        } elseif (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $id = $path;
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : null;
    }

    public static function uploadPath(?string $path): ?string
    {
        return is_string($path) && preg_match('~^public/uploads/product/videos/[A-Za-z0-9_-]+\.(?:mp4|webm|ogv|mov|m4v)$~iD', $path)
            ? $path : null;
    }

    public static function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webm' => 'video/webm',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            'm4v' => 'video/x-m4v',
            default => 'video/mp4',
        };
    }

    public static function posterPath(?string $path): ?string
    {
        return is_string($path) && preg_match('~^public/uploads/product/video-posters/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)$~iD', $path)
            ? $path : null;
    }

    public static function deleteUnusedPoster(?string $path): void
    {
        $path = self::posterPath($path);
        if ($path && !Product::where('video_poster', $path)->exists()) {
            $absolutePath = base_path($path);
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }

    public static function deleteUnusedUpload(?string $path): void
    {
        $path = self::uploadPath($path);
        if ($path && !Product::where('pro_video_path', $path)->exists()) {
            $absolutePath = base_path($path);
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }
}
