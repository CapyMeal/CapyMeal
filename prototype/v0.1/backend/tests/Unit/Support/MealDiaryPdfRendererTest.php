<?php

namespace Tests\Unit\Support;

use App\Support\MealDiaryPdfRenderer;
use PHPUnit\Framework\TestCase;

class MealDiaryPdfRendererTest extends TestCase
{
    public function test_html_in_user_text_is_escaped(): void
    {
        $malicious = '<img src="http://169.254.169.254/latest/meta-data/">';

        $result = MealDiaryPdfRenderer::renderText($malicious);

        $this->assertStringNotContainsString('<img src="http://169.254.169.254', $result);
        $this->assertStringContainsString('&lt;img', $result);
    }

    public function test_script_tags_are_escaped(): void
    {
        $result = MealDiaryPdfRenderer::renderText('<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function test_plain_text_without_special_characters_is_unchanged(): void
    {
        $result = MealDiaryPdfRenderer::renderText('Cafe con leche y tostadas');

        $this->assertSame('Cafe con leche y tostadas', $result);
    }
}
