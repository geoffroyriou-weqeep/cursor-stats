<?php

use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\HttpCursorUsageClient;
use App\Services\Cursor\ReportingPeriod;
use App\Services\Cursor\SessionCredential;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'cursor_stats.api_base_url' => 'https://cursor.com',
        'cursor_stats.page_size' => 2,
    ]);
});

it('paginates through all usage events and maps token fields', function () {
    Http::fake([
        'cursor.com/api/dashboard/get-filtered-usage-events' => Http::sequence()
            ->push([
                'totalUsageEventsCount' => 3,
                'usageEventsDisplay' => [
                    [
                        'timestamp' => 1000,
                        'isTokenBasedCall' => true,
                        'tokenUsage' => [
                            'inputTokens' => 10,
                            'outputTokens' => 5,
                            'cacheReadTokens' => 2,
                        ],
                        'chargedCents' => 1.0,
                    ],
                    [
                        'timestamp' => 2000,
                        'isTokenBasedCall' => false,
                        'tokenUsage' => [
                            'inputTokens' => 999,
                            'outputTokens' => 999,
                            'cacheReadTokens' => 999,
                        ],
                        'chargedCents' => 50.0,
                    ],
                ],
            ])
            ->push([
                'totalUsageEventsCount' => 3,
                'usageEventsDisplay' => [
                    [
                        'timestamp' => 3000,
                        'isTokenBasedCall' => true,
                        'tokenUsage' => [
                            'inputTokens' => 20,
                            'outputTokens' => 1,
                            'cacheReadTokens' => 0,
                        ],
                        'chargedCents' => 0.5,
                    ],
                ],
            ]),
    ]);

    $resolver = new class implements SessionCredentialResolver
    {
        public function resolve(): SessionCredential
        {
            return new SessionCredential('WorkosCursorSessionToken=test-token');
        }
    };

    $client = new HttpCursorUsageClient($resolver);
    $period = new ReportingPeriod(0, 9999999999999, 'Test');

    $events = $client->fetchUsageEvents($period);

    expect($events)->toHaveCount(3)
        ->and($events[0]->inputTokens)->toBe(10)
        ->and($events[0]->outputTokens)->toBe(5)
        ->and($events[0]->cacheReadTokens)->toBe(2)
        ->and($events[1]->isTokenBasedCall)->toBeFalse()
        ->and($events[1]->inputTokens)->toBe(0)
        ->and($events[2]->inputTokens)->toBe(20);

    Http::assertSentCount(2);

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $request->url() === 'https://cursor.com/api/dashboard/get-filtered-usage-events'
            && $request->hasHeader('Cookie', 'WorkosCursorSessionToken=test-token')
            && $request->hasHeader('Origin', 'https://cursor.com')
            && $body['page'] === 1
            && $body['pageSize'] === 2
            && $body['startDate'] === '0'
            && $body['endDate'] === '9999999999999';
    });
});

it('throws when cursor rejects the session', function () {
    Http::fake([
        'cursor.com/api/dashboard/get-filtered-usage-events' => Http::response([], 401),
    ]);

    $resolver = new class implements SessionCredentialResolver
    {
        public function resolve(): SessionCredential
        {
            return new SessionCredential('WorkosCursorSessionToken=bad');
        }
    };

    $client = new HttpCursorUsageClient($resolver);

    $client->fetchUsageEvents(new ReportingPeriod(0, 1, 'Test'));
})->throws(CursorSessionUnavailableException::class);

it('throws when cursor returns authorize redirect 404', function () {
    Http::fake([
        'cursor.com/api/dashboard/get-filtered-usage-events' => Http::response(
            ['message' => 'Cannot POST /user_management/authorize?client_id=test'],
            404,
        ),
    ]);

    $resolver = new class implements SessionCredentialResolver
    {
        public function resolve(): SessionCredential
        {
            return new SessionCredential('WorkosCursorSessionToken=bad');
        }
    };

    $client = new HttpCursorUsageClient($resolver);

    $client->fetchUsageEvents(new ReportingPeriod(0, 1, 'Test'));
})->throws(CursorSessionUnavailableException::class);
