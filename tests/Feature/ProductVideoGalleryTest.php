<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Category;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\Product;
use App\Models\Productimage;
use App\Support\ProductVideo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProductVideoGalleryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        GeneralSetting::firstOrCreate(['status' => 1], [
            'name' => 'Bilai Ghor', 'white_logo' => 'logo.png',
            'dark_logo' => 'logo.png', 'favicon' => 'favicon.ico',
        ]);
        Contact::firstOrCreate(['status' => 1], [
            'id' => (Contact::max('id') ?? 0) + 1,
            'phone' => '01900000000', 'email' => 'info@example.test', 'address' => 'Dhaka',
        ]);
    }

    public function test_uploaded_video_appears_after_gallery_images(): void
    {
        $product = $this->makeProduct();
        $path = 'public/uploads/product/videos/video-test-'.Str::lower(Str::random(12)).'.mp4';
        file_put_contents(base_path($path), "\x00\x00\x00\x18ftypisom");
        try {
            $product->update(['pro_video_type' => 'upload', 'pro_video_path' => $path]);
            $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();
            $this->assertStringContainsString('aria-label="Show product image 1"', $html);
            $this->assertStringContainsString('aria-label="Show product video"', $html);
            $this->assertStringContainsString('<video controls playsinline preload="metadata"', $html);
            $this->assertStringContainsString('<source src="'.asset($path).'" type="video/mp4">', $html);
            $this->assertLessThan(strpos($html, 'bpd-video-thumb'), strpos($html, 'aria-label="Show product image 1"'));
            $this->assertMatchesRegularExpression('/<button[^>]*bpd-video-thumb[^>]*>\s*<span class="bpd-video-thumb-play"/s', $html);
            $this->assertStringNotContainsString(' poster="', $html);
        } finally {
            Cache::forget('product_details_'.$product->slug);
            unlink(base_path($path));
        }
    }

    public function test_uploaded_video_poster_is_used_by_thumbnail_and_player(): void
    {
        $product = $this->makeProduct();
        $videoPath = 'public/uploads/product/videos/video-test-'.Str::lower(Str::random(12)).'.mp4';
        $posterPath = 'public/uploads/product/video-posters/poster-test-'.Str::lower(Str::random(12)).'.png';
        if (!is_dir(base_path('public/uploads/product/video-posters'))) mkdir(base_path('public/uploads/product/video-posters'), 0755, true);
        file_put_contents(base_path($videoPath), "\x00\x00\x00\x18ftypisom");
        file_put_contents(base_path($posterPath), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lZkAAAAASUVORK5CYII='));
        try {
            $product->update(['pro_video_type' => 'upload', 'pro_video_path' => $videoPath, 'video_poster' => $posterPath]);
            $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/<button[^>]*bpd-video-thumb[^>]*>\s*<img src="'.preg_quote(asset($posterPath), '/').'"[^>]*>\s*<span class="bpd-video-thumb-play"/s', $html);
            $this->assertMatchesRegularExpression('/<video controls playsinline preload="metadata"\s+poster="'.preg_quote(asset($posterPath), '/').'"/s', $html);
            $this->assertStringContainsString('aria-label="Show product image 1"', $html);
        } finally {
            Cache::forget('product_details_'.$product->slug);
            unlink(base_path($videoPath));
            unlink(base_path($posterPath));
        }
    }

    public function test_main_product_image_is_the_thumbnail_fallback_when_no_poster_exists(): void
    {
        $product = $this->makeProduct();
        $videoPath = 'public/uploads/product/videos/video-test-'.Str::lower(Str::random(12)).'.mp4';
        file_put_contents(base_path($videoPath), "\x00\x00\x00\x18ftypisom");
        try {
            $imagePath = 'public/uploads/product/1759499529-61nhot+9ffl.jpg';
            $product->images()->update(['image' => $imagePath]);
            $product->update(['pro_video_type' => 'upload', 'pro_video_path' => $videoPath]);
            $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();
            $this->assertMatchesRegularExpression('/<button[^>]*bpd-video-thumb[^>]*>\s*<img src="'.preg_quote(asset($imagePath), '/').'"[^>]*>\s*<span class="bpd-video-thumb-play"/s', $html);
            $this->assertStringNotContainsString(' poster="', $html);
        } finally {
            Cache::forget('product_details_'.$product->slug);
            unlink(base_path($videoPath));
        }
    }

    public function test_youtube_and_video_free_products_render_the_right_gallery(): void
    {
        $product = $this->makeProduct();
        $product->update(['pro_video_type' => 'youtube', 'pro_video' => 'dQw4w9WgXcQ']);
        $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();
        $this->assertStringContainsString('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0', $html);
        $this->assertStringNotContainsString('<video controls playsinline', $html);

        $product->update(['pro_video_type' => null, 'pro_video' => null]);
        Cache::forget('product_details_'.$product->slug);
        $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();
        $this->assertStringContainsString('aria-label="Show product image 1"', $html);
        $this->assertStringNotContainsString('aria-label="Show product video"', $html);
        Cache::forget('product_details_'.$product->slug);
    }

    public function test_blank_edit_preserves_video_and_replacement_cleans_up_only_unused_file(): void
    {
        $product = $this->makeProduct();
        $oldPath = 'public/uploads/product/videos/video-test-'.Str::lower(Str::random(12)).'.mp4';
        file_put_contents(base_path($oldPath), "\x00\x00\x00\x18ftypisom");
        $product->update(['pro_video_type' => 'upload', 'pro_video_path' => $oldPath]);
        $otherProduct = $this->makeProduct();
        $otherProduct->update(['pro_video_type' => 'upload', 'pro_video_path' => $oldPath]);
        $method = new \ReflectionMethod(ProductController::class, 'handleVideoInput');
        $controller = new ProductController();
        $newPath = null;
        try {
            $input = [];
            $method->invokeArgs($controller, [Request::create('/', 'POST', ['pro_video_source' => 'upload']), &$input, $product]);
            $this->assertSame([], $input);
            $this->assertSame($oldPath, $product->fresh()->pro_video_path);

            $file = UploadedFile::fake()->createWithContent('clip.mp4', "\x00\x00\x00\x18ftypisom\x00\x00\x00\x00isomiso2");
            $request = Request::create('/', 'POST', ['pro_video_source' => 'upload'], [], ['pro_video_file' => $file]);
            $method->invokeArgs($controller, [$request, &$input, $product]);
            $newPath = $input['pro_video_path'];
            $this->assertNotSame($oldPath, $newPath);
            $this->assertFileExists(base_path($newPath));

            $product->update($input);
            ProductVideo::deleteUnusedUpload($oldPath);
            $this->assertFileExists(base_path($oldPath));
            $otherProduct->update(['pro_video_type' => null, 'pro_video_path' => null]);
            ProductVideo::deleteUnusedUpload($oldPath);
            $this->assertFileDoesNotExist(base_path($oldPath));
            $this->assertSame($newPath, $product->fresh()->pro_video_path);

            $remove = [];
            $method->invokeArgs($controller, [Request::create('/', 'POST', ['pro_video_remove' => '1']), &$remove, $product]);
            $this->assertSame(['pro_video' => null, 'pro_video_type' => null, 'pro_video_path' => null], $remove);
        } finally {
            foreach ([$oldPath, $newPath] as $path) {
                if ($path && is_file(base_path($path))) unlink(base_path($path));
            }
        }
    }

    public function test_poster_edit_keeps_replaces_and_removes_without_changing_video(): void
    {
        $product = $this->makeProduct();
        $oldPath = 'public/uploads/product/video-posters/poster-test-'.Str::lower(Str::random(12)).'.png';
        if (!is_dir(base_path('public/uploads/product/video-posters'))) mkdir(base_path('public/uploads/product/video-posters'), 0755, true);
        file_put_contents(base_path($oldPath), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lZkAAAAASUVORK5CYII='));
        $product->update(['pro_video_type' => 'upload', 'pro_video_path' => 'public/uploads/product/videos/1773056301-video.mp4', 'video_poster' => $oldPath]);
        $otherProduct = $this->makeProduct();
        $otherProduct->update(['video_poster' => $oldPath]);
        $method = new \ReflectionMethod(ProductController::class, 'handleVideoPosterInput');
        $controller = new ProductController();
        $newPath = null;
        try {
            $input = [];
            $method->invokeArgs($controller, [Request::create('/', 'POST'), &$input, $product]);
            $this->assertSame([], $input);
            $this->assertSame($oldPath, $product->fresh()->video_poster);

            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lZkAAAAASUVORK5CYII=');
            $file = UploadedFile::fake()->createWithContent('new-poster.png', $png);
            $request = Request::create('/', 'POST', [], [], ['video_poster_file' => $file]);
            $method->invokeArgs($controller, [$request, &$input, $product]);
            $newPath = $input['video_poster'];
            $this->assertNotSame($oldPath, $newPath);
            $this->assertFileExists(base_path($newPath));

            $product->update($input);
            ProductVideo::deleteUnusedPoster($oldPath);
            $this->assertFileExists(base_path($oldPath));
            $otherProduct->update(['video_poster' => null]);
            ProductVideo::deleteUnusedPoster($oldPath);
            $this->assertFileDoesNotExist(base_path($oldPath));
            $this->assertSame($newPath, $product->fresh()->video_poster);

            $remove = [];
            $method->invokeArgs($controller, [Request::create('/', 'POST', ['video_poster_remove' => '1']), &$remove, $product]);
            $this->assertSame(['video_poster' => null], $remove);
            $product->update($remove);
            ProductVideo::deleteUnusedPoster($newPath);
            $this->assertFileDoesNotExist(base_path($newPath));
            $this->assertSame('public/uploads/product/videos/1773056301-video.mp4', $product->fresh()->pro_video_path);
        } finally {
            foreach ([$oldPath, $newPath] as $path) {
                if ($path && is_file(base_path($path))) unlink(base_path($path));
            }
        }
    }

    private function makeProduct(): Product
    {
        $suffix = Str::lower(Str::random(10));
        $category = Category::create(['name' => 'Video Test', 'slug' => 'video-test-'.$suffix, 'parent_id' => 0, 'status' => 1]);
        $product = Product::create([
            'name' => 'Video Test Cat Toy', 'slug' => 'video-test-cat-toy-'.$suffix,
            'category_id' => $category->id, 'product_code' => 'VIDEO-'.$suffix,
            'purchase_price' => 10, 'new_price' => 20, 'stock' => 2,
            'status' => 1, 'approval_status' => 'approved',
        ]);
        Productimage::create(['product_id' => $product->id, 'image' => 'public/uploads/product/test-image.jpg', 'image_alt' => 'Cat toy']);

        return $product;
    }
}
