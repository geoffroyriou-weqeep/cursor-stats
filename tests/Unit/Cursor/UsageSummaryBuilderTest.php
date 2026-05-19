<?php

use App\Services\Cursor\UsageEventDto;
use App\Services\Cursor\UsageSummaryBuilder;

it('aggregates token totals and cost for token-based events only', function () {
    $events = [
        new UsageEventDto(
            timestamp: 1,
            isTokenBasedCall: true,
            inputTokens: 1000,
            outputTokens: 500,
            cacheReadTokens: 200,
            chargedCents: 1.5,
        ),
        new UsageEventDto(
            timestamp: 2,
            isTokenBasedCall: true,
            inputTokens: 2000,
            outputTokens: 100,
            cacheReadTokens: 50,
            chargedCents: 2.4,
        ),
        new UsageEventDto(
            timestamp: 3,
            isTokenBasedCall: false,
            inputTokens: 9999,
            outputTokens: 9999,
            cacheReadTokens: 9999,
            chargedCents: 99.0,
        ),
    ];

    $summary = (new UsageSummaryBuilder)->build($events);

    expect($summary->inputTokens)->toBe(3000)
        ->and($summary->outputTokens)->toBe(600)
        ->and($summary->cacheReadTokens)->toBe(250)
        ->and($summary->usageCostCents)->toBe(4)
        ->and($summary->eventCount)->toBe(3);
});

it('returns zeros when there are no events', function () {
    $summary = (new UsageSummaryBuilder)->build([]);

    expect($summary->inputTokens)->toBe(0)
        ->and($summary->outputTokens)->toBe(0)
        ->and($summary->cacheReadTokens)->toBe(0)
        ->and($summary->usageCostCents)->toBe(0)
        ->and($summary->eventCount)->toBe(0);
});

it('formats cost in euros with french separators', function () {
    $summary = (new UsageSummaryBuilder)->build([
        new UsageEventDto(1, true, 0, 0, 0, 1234.0),
    ]);

    expect($summary->formattedCost())->toBe('12,34 €');
});

it('formats token counts with french thousands separators', function () {
    $summary = (new UsageSummaryBuilder)->build([
        new UsageEventDto(1, true, 1_234_567, 0, 0, 0),
    ]);

    expect($summary->formattedTokens($summary->inputTokens))->toBe('1 234 567');
});
