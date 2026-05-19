<?php

use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\ReportingPeriod;
use App\Services\Cursor\UsageEventDto;

it('renders today usage summary on the dashboard by default', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
            ->with(Mockery::on(fn (ReportingPeriod $period) => $period->label === 'Aujourd\'hui'))
            ->andReturn([
                new UsageEventDto(1, true, 100, 50, 10, 2.5),
            ]);
    });

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Aujourd')
        ->assertSee('Input')
        ->assertSee('Montant réel')
        ->assertSee('0,03 €');
});

it('renders yesterday usage summary when preset is yesterday', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
            ->with(Mockery::on(fn (ReportingPeriod $period) => $period->label === 'Hier'))
            ->andReturn([
                new UsageEventDto(1, true, 200, 0, 0, 1.0),
            ]);
    });

    $response = $this->get('/?preset=yesterday');

    $response->assertOk()
        ->assertSee('Hier')
        ->assertSee('0,01 €');
});

it('renders last 7 days usage summary when preset is last_7_days', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
            ->with(Mockery::on(fn (ReportingPeriod $period) => str_starts_with($period->label, '7 derniers jours')))
            ->andReturn([
                new UsageEventDto(1, true, 10, 10, 10, 5.0),
            ]);
    });

    $response = $this->get('/?preset=last_7_days');

    $response->assertOk()
        ->assertSee('7 derniers jours')
        ->assertSee('0,05 €');
});

it('shows preset navigation with the active preset highlighted', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')->andReturn([]);
    });

    $this->get('/?preset=yesterday')
        ->assertOk()
        ->assertSee('aria-current="page"', false);
});

it('renders custom range usage summary when from and to are provided', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
            ->with(Mockery::on(fn (ReportingPeriod $period) => $period->label === 'Personnalisé (1–5 mai 2026)'))
            ->andReturn([
                new UsageEventDto(1, true, 300, 100, 0, 3.0),
            ]);
    });

    $response = $this->get('/?from=2026-05-01&to=2026-05-05');

    $response->assertOk()
        ->assertSee('Personnalisé')
        ->assertSee('0,03 €')
        ->assertSee('name="from"', false)
        ->assertSee('value="2026-05-01"', false)
        ->assertSee('value="2026-05-05"', false);
});

it('prefers custom range over preset when both query params are present', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
            ->with(Mockery::on(fn (ReportingPeriod $period) => str_starts_with($period->label, 'Personnalisé')))
            ->andReturn([]);
    });

    $this->get('/?preset=yesterday&from=2026-05-01&to=2026-05-05')
        ->assertOk()
        ->assertSee('Personnalisé');
});

it('rejects inverted custom date ranges', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldNotReceive('fetchUsageEvents');
    });

    $this->from('/?from=2026-05-01&to=2026-05-05')
        ->get('/?from=2026-05-10&to=2026-05-01')
        ->assertRedirect()
        ->assertSessionHasErrors('to');
});

it('falls back to today when preset query value is invalid', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
            ->with(Mockery::on(fn (ReportingPeriod $period) => $period->label === 'Aujourd\'hui'))
            ->andReturn([]);
    });

    $this->get('/?preset=invalid')
        ->assertOk()
        ->assertSee('Aujourd');
});

it('renders auth failure when session cookie is missing', function () {
    config([
        'cursor_stats.sqlite_path' => sys_get_temp_dir().'/cursor-stats-missing-'.uniqid().'.vscdb',
        'cursor_stats.session_cookie' => null,
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Session Cursor indisponible')
        ->assertSee('CURSOR_SESSION_COOKIE')
        ->assertSee('CURSOR_STATS_SQLITE_PATH')
        ->assertSee('Étapes recommandées');
});
