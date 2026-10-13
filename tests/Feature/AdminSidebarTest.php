<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The grouped admin sidebar: sections, permission-based visibility and the active section.
 */
class AdminSidebarTest extends TestCase
{
    use DatabaseTransactions;

    private const SECTIONS = ['Sales', 'Products &amp; Stock', 'Customers', 'Fraud Protection', 'Marketing',
        'Website Content', 'Finance', 'Team &amp; Access', 'Settings'];

    public function test_a_full_admin_sees_every_section_in_order(): void
    {
        $html = $this->actingAs($this->admin(), 'admin')->get(route('admin.dashboard'))->assertOk()->getContent();
        $menu = $this->sidebar($html);

        $positions = array_map(fn ($label) => strpos($menu, '<span class="bg-menu-group-label">' . $label . '</span>'), self::SECTIONS);
        $this->assertNotContains(false, $positions, 'every section label is shown');
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'sections appear in the agreed order');

        $this->assertStringContainsString('id="bg-menu-search"', $menu);
        $this->assertMatchesRegularExpression('/data-menu-group="settings" data-default-collapsed="1"/', $menu);
        $this->assertMatchesRegularExpression('/data-menu-group="sales" data-default-collapsed="0"/', $menu);
    }

    public function test_items_sit_in_their_sections_and_customers_left_the_users_submenu(): void
    {
        $menu = $this->sidebar($this->actingAs($this->admin(), 'admin')->get(route('admin.dashboard'))->getContent());

        $this->assertSectionHas($menu, 'Sales', ['Orders', 'POS System', 'Refunds', 'Delivery']);
        $this->assertSectionHas($menu, 'Products &amp; Stock', ['Products', 'Kitten Packs', 'Inventory', 'Purchases', 'Suppliers']);
        $this->assertSectionHas($menu, 'Customers', ['Customers', 'Reviews', 'Complaints', 'Contact Messages', 'Newsletter Subscribers']);
        $this->assertSectionHas($menu, 'Fraud Protection', ['Manual Fraud Check', 'Order Restriction', 'Fraud API Settings']);
        $this->assertSectionHas($menu, 'Finance', ['Fund / Account', 'Expenses', 'Reports']);
        $this->assertSectionHas($menu, 'Settings', ['Site Setting', 'Email Settings', 'API Integration', 'Cron Job', 'Error Log']);

        // The Users submenu now ends after Permissions.
        preg_match('/id="sidebar-users">(.*?)<\/div>/s', $menu, $users);
        $this->assertStringContainsString(route('permissions.index'), $users[1]);
        $this->assertStringNotContainsString(route('customers.index'), $users[1]);
        $this->assertSame(1, substr_count($menu, 'href="' . route('customers.index') . '"'));
    }

    public function test_a_limited_admin_sees_only_their_sections_without_empty_labels(): void
    {
        $menu = $this->sidebar($this->actingAs($this->limitedAdmin(['fraud-check', 'order-list']), 'admin')
            ->get(route('manualFraud.page'))->assertOk()->getContent());

        foreach (['Sales', 'Fraud Protection'] as $label) {
            $this->assertStringContainsString('<span class="bg-menu-group-label">' . $label . '</span>', $menu);
        }
        foreach (['Marketing', 'Finance', 'Team &amp; Access', 'Settings', 'Customers'] as $label) {
            $this->assertStringNotContainsString('<span class="bg-menu-group-label">' . $label . '</span>', $menu);
        }
        $this->assertStringNotContainsString(route('admin.fraud.index'), $menu);        // needs fraud-setting-list
        $this->assertStringNotContainsString(route('admin.order.restriction.setting.index'), $menu); // needs setting-list
    }

    public function test_a_deep_page_opens_its_section_and_submenu(): void
    {
        $product = Product::create([
            'product_type' => 'physical', 'name' => 'Sidebar Test Product', 'slug' => 'sidebar-test-' . Str::uuid(),
            'category_id' => 1, 'new_price' => 10, 'stock' => 0, 'status' => 1,
        ]);

        $menu = $this->sidebar($this->actingAs($this->admin(), 'admin')
            ->get(route('products.edit', $product->id))->assertOk()->getContent());

        $this->assertMatchesRegularExpression('/<li class="menuitem-active">\s*<a href="#siebar-product"/', $menu);
        $this->assertStringContainsString('<div class="collapse show" id="siebar-product">', $menu);
        $this->assertStringContainsString('<div class="collapse" id="sidebar-orders">', $menu);
    }

    // ---------------------------------------------------------------- helpers

    private function sidebar(string $html): string
    {
        $start = strpos($html, '<div id="sidebar-menu"');
        $end = strpos($html, 'class="bg-menu-empty"', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($html, $start, $end - $start);
    }

    /** Items of a section: the top-level names between its label and the next label. */
    private function assertSectionHas(string $menu, string $label, array $names): void
    {
        $parts = preg_split('/<li class="bg-menu-group"/', $menu);
        $section = collect($parts)->first(fn ($part) => str_contains($part, '<span class="bg-menu-group-label">' . $label . '</span>'));
        $this->assertNotNull($section, "section $label");

        preg_match_all('/<\/i>\s*<span>\s*([^<]+?)\s*<\/span>/', $section, $matches);
        $this->assertSame($names, array_values(array_filter($matches[1], fn ($n) => in_array($n, $names, true))), "items of $label");
    }

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Sidebar Admin', 'email' => 'sidebar-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $admin->assignRole(Role::findOrCreate('Admin', 'admin'));

        return $admin;
    }

    private function limitedAdmin(array $permissions): User
    {
        $role = Role::create(['name' => 'sidebar-limited-' . Str::random(8), 'guard_name' => 'admin']);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }
        $role->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::create([
            'name' => 'Limited Admin', 'email' => 'limited-' . Str::uuid() . '@example.test',
            'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
