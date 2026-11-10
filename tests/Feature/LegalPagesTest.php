<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        GeneralSetting::firstOrCreate(['status' => 1], [
            'name' => 'Bilai Ghor',
            'white_logo' => 'logo.png',
            'dark_logo' => 'logo.png',
            'favicon' => 'favicon.ico',
        ]);
        Contact::firstOrCreate(['status' => 1], [
            'id' => (Contact::max('id') ?? 0) + 1,
            'phone' => '01900000000',
            'email' => 'info@example.test',
            'address' => 'Dhaka',
        ]);
    }

    public function test_privacy_policy_renders_with_metadata_and_shared_footer_links(): void
    {
        $response = $this->get('/privacy-policy')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<title>Privacy Policy | Bilai Ghor</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://bilaighor.bd/privacy-policy"', $html);
        $this->assertStringContainsString('Read the Bilai Ghor Privacy Policy to understand how customer information is collected, used and protected.', $html);
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('Process and deliver orders</li>', $html);
        $this->assertStringContainsString('href="' . route('privacy.policy') . '"', $html);
        $this->assertStringContainsString('href="' . route('terms') . '"', $html);
    }

    public function test_terms_render_with_metadata_and_one_heading(): void
    {
        $response = $this->get('/terms')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('<title>Terms &amp; Conditions | Bilai Ghor</title>', $html);
        $this->assertStringContainsString('<link rel="canonical" href="https://bilaighor.bd/terms"', $html);
        $this->assertStringContainsString('Read the Bilai Ghor Terms &amp; Conditions covering orders, products, pricing, delivery and use of the website.', $html);
        $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('Changes to These Terms</h2>', $html);
    }

    public function test_old_privacy_url_redirects_permanently(): void
    {
        $this->get('/privay-policy')
            ->assertStatus(301)
            ->assertRedirect('/privacy-policy');
    }

    public function test_return_policy_is_public_and_has_requested_canonical_and_clear_terms(): void
    {
        foreach (['/return-policy', '/return-refund-policy'] as $path) {
            $html = $this->get($path)->assertOk()->getContent();

            $this->assertStringContainsString('<title>Return &amp; Refund Policy | Bilai Ghor</title>', $html);
            $this->assertStringContainsString('<link rel="canonical" href="https://bilaighor.bd/return-refund-policy"', $html);
            $this->assertSame(1, preg_match_all('/<h1\b/i', $html));
            $this->assertStringContainsString('3 calendar days of receiving the order', $html);
            $this->assertStringContainsString('7 calendar days of delivery', $html);
            $this->assertStringContainsString('5–7 business days', $html);
            $this->assertStringContainsString('href="' . route('return.policy') . '"', $html);
            $this->assertStringContainsString('href="' . route('contact') . '"', $html);
            $this->assertStringNotContainsString('name="robots" content="noindex', $html);
        }
    }
}
