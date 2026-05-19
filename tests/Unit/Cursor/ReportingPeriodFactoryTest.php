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

it('builds yesterday period for the previous calendar day in reporting timezone', function () {
    $now = CarbonImmutable::parse('2026-05-19 08:00:00', 'Europe/Paris');

    $period = (new ReportingPeriodFactory)->forPreset(DatePreset::Yesterday, $now);

    $start = CarbonImmutable::parse('2026-05-18 00:00:00', 'Europe/Paris');
    $end = CarbonImmutable::parse('2026-05-18 23:59:59.999999', 'Europe/Paris');

    expect($period->startMs)->toBe((int) $start->valueOf())
        ->and($period->endMs)->toBe((int) $end->valueOf())
        ->and($period->label)->toBe('Hier');
});

it('builds last 7 days period including today and six prior calendar days', function () {
    $now = CarbonImmutable::parse('2026-05-19 12:00:00', 'Europe/Paris');

    $period = (new ReportingPeriodFactory)->forPreset(DatePreset::Last7Days, $now);

    $start = CarbonImmutable::parse('2026-05-13 00:00:00', 'Europe/Paris');
    $end = CarbonImmutable::parse('2026-05-19 23:59:59.999999', 'Europe/Paris');

    expect($period->startMs)->toBe((int) $start->valueOf())
        ->and($period->endMs)->toBe((int) $end->valueOf())
        ->and($period->label)->toBe('7 derniers jours (13–19 mai 2026)');
});

it('spans last 7 days across month boundaries with a readable label', function () {
    $now = CarbonImmutable::parse('2026-05-03 10:00:00', 'Europe/Paris');

    $period = (new ReportingPeriodFactory)->forPreset(DatePreset::Last7Days, $now);

    $start = CarbonImmutable::parse('2026-04-27 00:00:00', 'Europe/Paris');
    $end = CarbonImmutable::parse('2026-05-03 23:59:59.999999', 'Europe/Paris');

    expect($period->startMs)->toBe((int) $start->valueOf())
        ->and($period->endMs)->toBe((int) $end->valueOf())
        ->and($period->label)->toBe('7 derniers jours (27 avril – 3 mai 2026)');
});

it('uses full calendar day on spring dst transition in reporting timezone', function () {
    $now = CarbonImmutable::parse('2026-03-29 14:00:00', 'Europe/Paris');

    $period = (new ReportingPeriodFactory)->forPreset(DatePreset::Today, $now);

    $start = CarbonImmutable::parse('2026-03-29 00:00:00', 'Europe/Paris');
    $end = CarbonImmutable::parse('2026-03-29 23:59:59.999999', 'Europe/Paris');

    expect($period->startMs)->toBe((int) $start->valueOf())
        ->and($period->endMs)->toBe((int) $end->valueOf());
});
