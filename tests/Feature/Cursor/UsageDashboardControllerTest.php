<?php

use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\UsageEventDto;

it('renders today usage summary on the dashboard', function () {
    config([
        'cursor_stats.session_cookie' => 'test-session-token',
        'cursor_stats.timezone' => 'Europe/Paris',
    ]);

    $this->mock(CursorUsageClient::class, function ($mock) {
        $mock->shouldReceive('fetchUsageEvents')
            ->once()
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

it('renders auth failure when session cookie is missing', function () {
    config(['cursor_stats.session_cookie' => null]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Session Cursor indisponible')
        ->assertSee('CURSOR_SESSION_COOKIE');
});
