<?php

namespace Tests\Unit;

use App\Support\GoogleSiteVerification;
use PHPUnit\Framework\TestCase;

class GoogleSiteVerificationTest extends TestCase
{
    public function test_it_extracts_a_token_from_the_dns_style_value(): void
    {
        $this->assertSame(
            'abc_123-XYZ',
            GoogleSiteVerification::normalize('google-site-verification=abc_123-XYZ')
        );
    }

    public function test_it_accepts_an_already_normalized_token(): void
    {
        $this->assertSame('abc_123-XYZ', GoogleSiteVerification::normalize('abc_123-XYZ'));
    }

    public function test_it_extracts_a_token_from_a_meta_tag(): void
    {
        $this->assertSame(
            'abc_123-XYZ',
            GoogleSiteVerification::normalize(
                '<meta name="google-site-verification" content="abc_123-XYZ">'
            )
        );
    }

    public function test_it_rejects_html_or_an_invalid_token(): void
    {
        $this->assertNull(GoogleSiteVerification::normalize('<script>alert(1)</script>'));
    }
}
