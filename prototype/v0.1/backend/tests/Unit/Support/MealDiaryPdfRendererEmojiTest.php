<?php

namespace Tests\Unit\Support;

use App\Support\MealDiaryPdfRenderer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MealDiaryPdfRendererEmojiTest extends TestCase
{
    protected function tearDown(): void
    {
        // Los emojis de prueba no deberían quedar en el cache real de disco
        // que usa la app -- se limpian después de cada test para que la
        // suite sea repetible sin depender del estado previo del disco.
        foreach (['1f355', '1f369', '1f96f'] as $filename) {
            @unlink(storage_path('app/emoji-cache/'.$filename.'.png'));
        }

        parent::tearDown();
    }

    public function test_emoji_is_converted_to_image_when_cdn_responds_successfully(): void
    {
        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('fake-png-bytes', 200),
        ]);

        $result = MealDiaryPdfRenderer::renderText('Pizza 🍕');

        $this->assertStringContainsString('<img src="data:image/png;base64,', $result);
        $this->assertFileExists(storage_path('app/emoji-cache/1f355.png'));
    }

    public function test_emoji_falls_back_to_plain_text_when_cdn_times_out(): void
    {
        // Patrón documentado por Laravel para simular una falla de conexión
        // (timeout, DNS, etc.) en vez de una respuesta HTTP con error.
        Http::fake(function () {
            throw new ConnectionException('Connection timed out.');
        });

        $result = MealDiaryPdfRenderer::renderText('Donut 🍩');

        $this->assertSame('Donut 🍩', $result);
        $this->assertFileDoesNotExist(storage_path('app/emoji-cache/1f369.png'));
    }

    public function test_emoji_falls_back_to_plain_text_when_cdn_returns_an_error_status(): void
    {
        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('not found', 404),
        ]);

        $result = MealDiaryPdfRenderer::renderText('Bagel 🥯');

        $this->assertSame('Bagel 🥯', $result);
        $this->assertFileDoesNotExist(storage_path('app/emoji-cache/1f96f.png'));
    }
}
