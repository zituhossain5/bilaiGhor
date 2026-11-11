<?php

namespace Tests\Unit;

use App\Support\ProductVideo;
use PHPUnit\Framework\TestCase;

class ProductVideoTest extends TestCase
{
    public function test_common_youtube_formats_and_untrusted_hosts(): void
    {
        foreach ([
            'dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=1',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'youtu.be/dQw4w9WgXcQ',
        ] as $value) {
            $this->assertSame('dQw4w9WgXcQ', ProductVideo::youtubeId($value));
        }
        $this->assertNull(ProductVideo::youtubeId('https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(ProductVideo::youtubeId('javascript:alert(1)'));
        $this->assertNull(ProductVideo::youtubeId('https://example.com/shorts/dQw4w9WgXcQ'));
    }

    public function test_uploaded_paths_stay_inside_the_product_video_directory(): void
    {
        $this->assertSame('public/uploads/product/videos/video-123_test.mp4', ProductVideo::uploadPath('public/uploads/product/videos/video-123_test.mp4'));
        $this->assertNull(ProductVideo::uploadPath('public/uploads/product/videos/../../private.mp4'));
        $this->assertNull(ProductVideo::uploadPath('https://example.com/video.mp4'));
        $this->assertNull(ProductVideo::uploadPath('public/uploads/product/videos/shell.php'));
        $this->assertSame('public/uploads/product/video-posters/poster-123.webp', ProductVideo::posterPath('public/uploads/product/video-posters/poster-123.webp'));
        $this->assertNull(ProductVideo::posterPath('public/uploads/product/video-posters/../../private.png'));
        $this->assertNull(ProductVideo::posterPath('public/uploads/product/video-posters/shell.php'));
    }
}
