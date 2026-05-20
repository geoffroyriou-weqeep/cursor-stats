<?php

use App\Services\Cursor\Attributors\UsageEventAttributor;
use App\Services\Cursor\Dto\ComposerSessionDto;
use App\Services\Cursor\Dto\ReportingPeriod;
use App\Services\Cursor\Dto\UsageEventDto;
use Carbon\CarbonImmutable;

function attributorTestPeriod(int $startMs, int $endMs): ReportingPeriod
{
    return new ReportingPeriod($startMs, $endMs, 'Test day');
}

it('attributes an event to the session with the latest createdAt among overlapping windows', function () {
    $period = attributorTestPeriod(0, 100_000);
    $now = CarbonImmutable::createFromTimestampMs(50_000, 'UTC');

    $sessions = [
        new ComposerSessionDto('older', 'Older', 0, 100_000, null, null, 'agent'),
        new ComposerSessionDto('newer', 'Newer', 50_000, 100_000, null, null, 'agent'),
    ];

    $events = [
        new UsageEventDto(75_000, true, 100, 0, 0, 1.0),
    ];

    $result = (new UsageEventAttributor)->attribute($events, $sessions, $period, $now);

    expect($result->unassigned)->toBe([])
        ->and($result->eventsFor('newer'))->toHaveCount(1)
        ->and($result->eventsFor('older'))->toBe([]);
});

it('leaves events unassigned when no session window contains the timestamp', function () {
    $period = attributorTestPeriod(0, 100_000);
    $now = CarbonImmutable::createFromTimestampMs(50_000, 'UTC');

    $sessions = [
        new ComposerSessionDto('a', 'A', 10_000, 20_000, null, null, 'agent'),
    ];

    $events = [
        new UsageEventDto(5_000, true, 100, 0, 0, 1.0),
        new UsageEventDto(90_000, true, 200, 0, 0, 2.0),
    ];

    $result = (new UsageEventAttributor)->attribute($events, $sessions, $period, $now);

    expect($result->unassigned)->toHaveCount(2)
        ->and($result->byComposerId)->toBe([]);
});

it('uses daily period end when lastUpdatedAt is null', function () {
    $period = attributorTestPeriod(0, 100_000);
    $now = CarbonImmutable::createFromTimestampMs(200_000, 'UTC');

    $sessions = [
        new ComposerSessionDto('open', 'Open', 0, null, null, null, 'agent'),
    ];

    $events = [
        new UsageEventDto(99_000, true, 50, 0, 0, 0.5),
    ];

    $result = (new UsageEventAttributor)->attribute($events, $sessions, $period, $now);

    expect($result->eventsFor('open'))->toHaveCount(1);
});

it('attributes headless and non-token events with the same rules', function () {
    $period = attributorTestPeriod(0, 100_000);
    $now = CarbonImmutable::createFromTimestampMs(50_000, 'UTC');

    $sessions = [
        new ComposerSessionDto('only', 'Only', 0, 100_000, null, null, 'agent'),
    ];

    $events = [
        new UsageEventDto(10_000, false, 0, 0, 0, 0),
        new UsageEventDto(20_000, true, 10, 0, 0, 1.0),
    ];

    $result = (new UsageEventAttributor)->attribute($events, $sessions, $period, $now);

    expect($result->eventsFor('only'))->toHaveCount(2);
});
