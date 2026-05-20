<?php

use App\Services\Cursor\Contracts\ComposerSessionRegistry;
use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Dto\ComposerSessionDto;
use App\Services\Cursor\Dto\ReportingPeriod;
use App\Services\Cursor\Dto\UsageEventDto;
use Carbon\CarbonImmutable;

function mockDashboardServices(
    array $events = [],
    array $sessions = [],
    ?callable $globalPeriodMatcher = null,
): void {
    test()->mock(CursorUsageClient::class, function ($mock) use ($events, $globalPeriodMatcher) {
        if ($globalPeriodMatcher === null) {
            $mock->shouldReceive('fetchUsageEvents')->twice()->andReturn($events);
        } else {
            $mock->shouldReceive('fetchUsageEvents')
                ->once()
                ->with(Mockery::on($globalPeriodMatcher))
                ->andReturn($events);
            $mock->shouldReceive('fetchUsageEvents')
                ->once()
                ->with(Mockery::on(fn (ReportingPeriod $period) => $period->label === 'Aujourd\'hui'))
                ->andReturn($events);
        }
    });

    test()->mock(ComposerSessionRegistry::class, function ($mock) use ($sessions) {
        $mock->shouldReceive('listAll')->once()->andReturn($sessions);
    });
}

function sessionDto(
    string $id,
    int $createdAtMs = 0,
    ?int $lastUpdatedAtMs = null,
    string $name = 'Test session',
): ComposerSessionDto {
    return new ComposerSessionDto(
        composerId: $id,
        name: $name,
        createdAtMs: $createdAtMs,
        lastUpdatedAtMs: $lastUpdatedAtMs,
        workspacePath: '/Users/dev/project',
        workspaceHash: null,
        unifiedMode: 'agent',
    );
}

it('renders today usage summary on the dashboard by default', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    mockDashboardServices([
        new UsageEventDto(1, true, 100, 50, 10, 2.5),
    ]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Aujourd')
        ->assertSee('Par période')
        ->assertSee('Par fil')
        ->assertSee('lg:grid-cols-2', false)
        ->assertSee('Input')
        ->assertSee('Output')
        ->assertSee('Cache read')
        ->assertSee('Contexte moyen')
        ->assertSee('Moyenne des tokens envoyés au modèle, par appel.')
        ->assertSee('Montant réel')
        ->assertSee('0,03 €')
        ->assertSee('Choisir un fil')
        ->assertDontSee('usage inclus valorisé');
});

it('formats large token totals with thousands separators', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    mockDashboardServices([
        new UsageEventDto(1, true, 1_234_567, 89_012, 3_456, 0),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('1 234 567', false)
        ->assertSee('89 012', false)
        ->assertSee('3 456', false);
});

it('displays average context size on the dashboard', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    mockDashboardServices([
        new UsageEventDto(1, true, 1000, 0, 0, 0),
        new UsageEventDto(2, true, 3000, 0, 0, 0),
        new UsageEventDto(3, false, 9_999_999, 0, 0, 0),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Contexte moyen')
        ->assertSee('2 000', false);
});

it('renders yesterday usage summary when preset is yesterday', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    mockDashboardServices(
        [new UsageEventDto(1, true, 200, 0, 0, 1.0)],
        globalPeriodMatcher: fn (ReportingPeriod $period) => $period->label === 'Hier',
    );

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

    mockDashboardServices(
        [new UsageEventDto(1, true, 10, 10, 10, 5.0)],
        globalPeriodMatcher: fn (ReportingPeriod $period) => str_starts_with($period->label, '7 derniers jours'),
    );

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

    mockDashboardServices();

    $this->get('/?preset=yesterday')
        ->assertOk()
        ->assertSee('aria-current="page"', false);
});

it('renders custom range usage summary when from and to are provided', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    mockDashboardServices(
        [new UsageEventDto(1, true, 300, 100, 0, 3.0)],
        globalPeriodMatcher: fn (ReportingPeriod $period) => $period->label === 'Personnalisé (1–5 mai 2026)',
    );

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

    mockDashboardServices(
        [],
        globalPeriodMatcher: fn (ReportingPeriod $period) => str_starts_with($period->label, 'Personnalisé'),
    );

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

    mockDashboardServices();

    $this->get('/?preset=invalid')
        ->assertOk()
        ->assertSee('Aujourd');
});

it('redirects when composer query param is not in the daily session list', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    mockDashboardServices([], [
        sessionDto('11111111-1111-1111-1111-111111111111'),
    ]);

    $this->get('/?composer=22222222-2222-2222-2222-222222222222')
        ->assertRedirect('/');
});

it('shows selected session summary when composer param is valid', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-20 12:00:00', 'Europe/Paris'));

    $composerId = '11111111-1111-1111-1111-111111111111';
    $dayStart = CarbonImmutable::now('Europe/Paris')->startOfDay()->valueOf();
    $dayEnd = CarbonImmutable::now('Europe/Paris')->endOfDay()->valueOf();

    mockDashboardServices(
        [
            new UsageEventDto($dayStart + 1_000, true, 500, 0, 0, 2.0),
            new UsageEventDto($dayStart + 2_000, true, 1500, 0, 0, 3.0),
            new UsageEventDto($dayStart + 3_000, false, 0, 0, 0, 0),
        ],
        [
            sessionDto($composerId, $dayStart, $dayEnd, 'My agent thread'),
        ],
    );

    $this->get('/?composer='.$composerId)
        ->assertOk()
        ->assertSee('My agent thread')
        ->assertSee('id="composer"', false)
        ->assertSee('token-based')
        ->assertSee('0,05 €')
        ->assertDontSee('Sélectionnez un fil dans la liste déroulante');

    CarbonImmutable::setTestNow();
});

it('preserves composer query param on preset links when valid', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $composerId = '11111111-1111-1111-1111-111111111111';

    mockDashboardServices([], [
        sessionDto($composerId),
    ]);

    $this->get('/?composer='.$composerId)
        ->assertOk()
        ->assertSee('composer='.$composerId, false)
        ->assertSee('preset=yesterday', false);
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
