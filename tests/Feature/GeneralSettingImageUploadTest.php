<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\GeneralSettingController;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class GeneralSettingImageUploadTest extends TestCase
{
    public function test_white_and_dark_logos_are_saved_to_the_public_settings_directory(): void
    {
        $method = new ReflectionMethod(GeneralSettingController::class, 'storeSettingImage');
        $controller = app(GeneralSettingController::class);
        $savedFiles = [];

        try {
            foreach (['white_logo' => 'White Logo.png', 'dark_logo' => 'Dark Logo.png'] as $field => $name) {
                $path = $method->invoke(
                    $controller,
                    UploadedFile::fake()->image($name, 240, 80),
                    $field
                );

                $absolutePath = public_path(substr($path, strlen('public/')));
                $savedFiles[] = $absolutePath;

                $this->assertStringStartsWith('public/uploads/settings/', $path);
                $this->assertStringEndsWith('.webp', $path);
                $this->assertStringContainsString(str_replace(' ', '-', strtolower(pathinfo($name, PATHINFO_FILENAME))), $path);
                $this->assertFileExists($absolutePath);
            }
        } finally {
            File::delete($savedFiles);
        }
    }
}
