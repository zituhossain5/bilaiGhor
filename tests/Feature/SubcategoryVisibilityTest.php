<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\GeneralSetting;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\SitemapService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubcategoryVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_edit_toggle_persists_off_and_on_and_updates_homepage(): void
    {
        // Model saves queue a sitemap rebuild; tests must not write the public sitemap.
        $this->app->instance(SitemapService::class, new class extends SitemapService {
            public function generate(?string $path = null): array { return ['path' => '', 'urls' => 0]; }
        });

        $admin = User::create([
            'name' => 'Visibility Admin',
            'email' => 'visibility-'.Str::uuid().'@example.test',
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));
        $this->actingAs($admin, 'admin');

        GeneralSetting::firstOrCreate(['status' => 1], [
            'name' => 'Bilai Ghor', 'white_logo' => 'logo.png',
            'dark_logo' => 'logo.png', 'favicon' => 'favicon.ico',
        ]);
        Contact::firstOrCreate(['status' => 1], [
            'id' => (Contact::max('id') ?? 0) + 1,
            'phone' => '01900000000', 'email' => 'info@example.test', 'address' => 'Dhaka',
        ]);

        $category = Category::create([
            'name' => 'Visibility Test', 'slug' => 'visibility-test-'.Str::uuid(),
            'parent_id' => 0, 'status' => 1,
        ]);

        foreach (['Cat Toys', 'Food & Water Bowl'] as $name) {
            $subcategory = Subcategory::create([
                'subcategoryName' => $name,
                'slug' => Str::slug($name).'-'.Str::random(8),
                'category_id' => $category->id,
                'status' => 1,
            ]);

            Cache::forget('frontend_homepage_v3');
            $this->assertTrue($this->homepageSubcategories()->contains('id', $subcategory->id));

            $this->post(route('subcategories.update'), [
                'id' => $subcategory->id,
                'category_id' => $category->id,
                'subcategoryName' => $name,
                'status' => '0',
            ])->assertRedirect(route('subcategories.index'));

            $this->assertSame(0, (int) $subcategory->fresh()->status);
            $this->assertFalse(Cache::has('frontend_homepage_v3'));
            $editHtml = $this->get(route('subcategories.edit', $subcategory->id))->assertOk()->getContent();
            $this->assertStringContainsString('<input type="hidden" name="status" value="0">', $editHtml);
            $this->assertDoesNotMatchRegularExpression(
                '/<input[^>]*type="checkbox"[^>]*name="status"[^>]*checked/i',
                $editHtml
            );
            $this->assertFalse($this->homepageSubcategories()->contains('id', $subcategory->id));
            $this->get(route('home'))->assertOk()
                ->assertDontSee(route('subcategory', $subcategory->fresh()->slug));

            $this->post(route('subcategories.update'), [
                'id' => $subcategory->id,
                'category_id' => $category->id,
                'subcategoryName' => $name,
                'status' => '1',
            ])->assertRedirect(route('subcategories.index'));

            $this->assertSame(1, (int) $subcategory->fresh()->status);
            $this->assertMatchesRegularExpression(
                '/<input[^>]*type="checkbox"[^>]*name="status"[^>]*checked/i',
                $this->get(route('subcategories.edit', $subcategory->id))->assertOk()->getContent()
            );
            $this->assertTrue($this->homepageSubcategories()->contains('id', $subcategory->id));
            $this->get(route('home'))->assertOk()
                ->assertSee(route('subcategory', $subcategory->fresh()->slug));
        }

        $this->assertStringContainsString(
            '<input type="hidden" name="status" value="0">',
            $this->get(route('subcategories.create'))->assertOk()->getContent()
        );
    }

    private function homepageSubcategories()
    {
        $controller = app(\App\Http\Controllers\Frontend\FrontendController::class);
        $method = new \ReflectionMethod($controller, 'getHomepageData');

        return $method->invoke($controller)['homeSubcategories'];
    }
}
