<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_responses_include_the_standard_security_headers(): void
    {
        $response = $this->getJson('/api/csrf-token');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains');
    }

    public function test_security_headers_are_present_even_on_an_error_response(): void
    {
        // Un 422 (validación) también pasa por el grupo "api" -- confirma
        // que el middleware no depende de que la request haya sido exitosa.
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
