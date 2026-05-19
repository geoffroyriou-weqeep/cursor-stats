<?php

use App\Services\Cursor\DatePreset;
use App\Services\Cursor\ReportingPeriodFactory;
use Carbon\CarbonImmutable;

beforeEach(function () {
    config(['cursor_stats.timezone' => 'Europe/Paris']);
});

it('builds today period from midnight to end of day in reporting timezone', function () {
    $now = CarbonImmutable::parse('2026-05-19 15:30:00', 'Europe/Paris');

    $period = (new ReportingPeriodFactory)->forPreset(DatePreset::Today, $now);

    $start = CarbonImmutable::parse('2026-05-19 00:00:00', 'Europe/Paris');
    $end = CarbonImmutable::parse('2026-05-19 23:59:59.999999', 'Europe/Paris');

    expect($period->startMs)->toBe((int) $start->valueOf())
        ->and($period->endMs)->toBe((int) $end->valueOf())
        ->and($period->label)->toBe('Aujourd\'hui');
});
