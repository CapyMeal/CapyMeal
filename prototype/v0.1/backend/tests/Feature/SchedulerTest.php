<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.scheduler_token' => 'test-scheduler-token']);

        // El endpoint está throttled igual que el resto de rutas sensibles --
        // sin esto los intentos se acumulan entre tests del mismo proceso.
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_valid_token_runs_the_scheduler(): void
    {
        $response = $this->postJson('/api/internal/scheduler/run', [], [
            'Authorization' => 'Bearer test-scheduler-token',
        ]);

        $response->assertOk();
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->postJson('/api/internal/scheduler/run')->assertStatus(401);
    }

    public function test_wrong_token_is_rejected(): void
    {
        $response = $this->postJson('/api/internal/scheduler/run', [], [
            'Authorization' => 'Bearer lo-que-sea',
        ]);

        $response->assertStatus(401);
    }

    public function test_endpoint_is_rate_limited_after_5_requests_per_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/internal/scheduler/run', [], [
                'Authorization' => 'Bearer test-scheduler-token',
            ])->assertOk();
        }

        $this->postJson('/api/internal/scheduler/run', [], [
            'Authorization' => 'Bearer test-scheduler-token',
        ])->assertStatus(429);
    }

    // Este es el punto realmente frágil de todo el diseño: routes/console.php
    // usa ->dailyAt('06:10') en vez de ->daily() porque nada más que el
    // workflow de GitHub Actions (.github/workflows/scheduler.yml) invoca
    // schedule:run, una sola vez al día, a las 6:10 UTC -- si ese horario
    // dejara de coincidir con el de acá, sanctum:prune-expired dejaría de
    // correr en silencio (la tarea nunca se consideraría "due"). Se prueba
    // directo contra Schedule::events() en vez de a través del endpoint
    // porque schedule:run corre cada tarea en un subproceso de SO aparte
    // (Symfony Process) con su propia conexión a la base de datos -- no ve
    // los datos creados dentro de la transacción de este test (RefreshDatabase).
    public function test_prune_expired_task_is_due_at_the_scheduled_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 06:10:00', 'UTC'));

        $pruneEvents = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'sanctum:prune-expired'));

        $this->assertNotEmpty($pruneEvents, 'sanctum:prune-expired no está registrado en el scheduler.');
        $pruneEvents->each(fn ($event) => $this->assertTrue(
            $event->isDue($this->app),
            'sanctum:prune-expired no está "due" a las 06:10 UTC -- revisar que coincida con el cron de scheduler.yml.'
        ));
    }

    public function test_prune_expired_task_is_not_due_a_minute_later(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 06:11:00', 'UTC'));

        $pruneEvents = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'sanctum:prune-expired'));

        $pruneEvents->each(fn ($event) => $this->assertFalse($event->isDue($this->app)));
    }
}
