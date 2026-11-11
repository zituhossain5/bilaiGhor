<?php

namespace Tests\Unit;

use App\Support\SeoText;
use PHPUnit\Framework\TestCase;

class SeoTextParagraphsTest extends TestCase
{
    public function test_intro_keeps_paragraph_breaks_and_removes_html(): void
    {
        $this->assertSame(
            "Wet food for cats.\n\nCompare pouches and prices.",
            SeoText::plainParagraphs("<strong>Wet food</strong> for cats.\r\n\r\nCompare pouches and prices.")
        );
    }

    public function test_empty_intro_is_null(): void
    {
        $this->assertNull(SeoText::plainParagraphs("  \n\n  "));
    }
}
