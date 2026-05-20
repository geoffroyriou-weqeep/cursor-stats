<?php

use App\Services\Cursor\Builders\DailyComposerSessionListBuilder;
use App\Services\Cursor\Dto\ComposerSessionDto;
use App\Services\Cursor\Dto\ReportingPeriod;
use Carbon\CarbonImmutable;

it('includes sessions whose window intersects the daily period', function () {
    $period = new ReportingPeriod(50_000, 150_000, 'Today');
    $now = CarbonImmutable::createFromTimestampMs(200_000, 'UTC');

    $sessions = [
        new ComposerSessionDto('yesterday-still-open', 'Y', 0, null, null, null, 'agent'),
        new ComposerSessionDto('ended-before', 'E', 0, 40_000, null, null, 'agent'),
        new ComposerSessionDto('starts-after', 'S', 160_000, 200_000, null, null, 'agent'),
        new ComposerSessionDto('inside', 'I', 60_000, 120_000, null, null, 'agent'),
    ];

    $list = (new DailyComposerSessionListBuilder)->build($sessions, $period, $now);

    expect(array_map(fn ($s) => $s->composerId, $list))
        ->toContain('yesterday-still-open', 'inside')
        ->and($list)->toHaveCount(2);
});

it('sorts by lastUpdatedAt descending with nulls last using createdAt', function () {
    $period = new ReportingPeriod(0, 1_000_000, 'Today');
    $now = CarbonImmutable::createFromTimestampMs(500_000, 'UTC');

    $sessions = [
        new ComposerSessionDto('null-updated', 'N', 0, null, null, null, 'agent'),
        new ComposerSessionDto('recent', 'R', 0, 300_000, null, null, 'agent'),
        new ComposerSessionDto('older', 'O', 0, 100_000, null, null, 'agent'),
    ];

    $list = (new DailyComposerSessionListBuilder)->build($sessions, $period, $now);

    expect(array_map(fn ($s) => $s->composerId, $list))
        ->toBe(['recent', 'older', 'null-updated']);
});
