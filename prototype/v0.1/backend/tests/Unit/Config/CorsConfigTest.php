<?php

namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

class CorsConfigTest extends TestCase
{
    private function loadConfigWithFrontendUrl(?string $value): array
    {
        if ($value === null) {
            putenv('FRONTEND_URL');
            unset($_ENV['FRONTEND_URL'], $_SERVER['FRONTEND_URL']);
        } else {
            putenv("FRONTEND_URL={$value}");
            $_ENV['FRONTEND_URL'] = $value;
            $_SERVER['FRONTEND_URL'] = $value;
        }

        return require dirname(__DIR__, 3).'/config/cors.php';
    }

    protected function tearDown(): void
    {
        putenv('FRONTEND_URL');
        unset($_ENV['FRONTEND_URL'], $_SERVER['FRONTEND_URL']);

        parent::tearDown();
    }

    public function test_wildcard_frontend_url_is_never_added_to_allowed_origins(): void
    {
        $config = $this->loadConfigWithFrontendUrl('*');

        $this->assertNotContains('*', $config['allowed_origins']);
    }

    public function test_missing_frontend_url_falls_back_to_dev_origins_only(): void
    {
        $config = $this->loadConfigWithFrontendUrl(null);

        $this->assertSame(
            ['http://localhost:5173', 'http://localhost:5174', 'http://localhost:4173'],
            $config['allowed_origins']
        );
    }

    public function test_real_frontend_url_is_added_without_trailing_slash(): void
    {
        $config = $this->loadConfigWithFrontendUrl('https://capy-meal.vercel.app/');

        $this->assertContains('https://capy-meal.vercel.app', $config['allowed_origins']);
    }
}
