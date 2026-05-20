<?php

namespace App\Services\Cursor\Builders;

use App\Services\Cursor\Dto\UsageEventDto;
use App\Services\Cursor\Dto\UsageSummary;

final class UsageSummaryBuilder
{
    /**
     * @param  iterable<UsageEventDto>  $events
     */
    public function build(iterable $events): UsageSummary
    {
        $inputTokens = 0;
        $outputTokens = 0;
        $cacheReadTokens = 0;
        $usageCostCents = 0.0;
        $eventCount = 0;
        $tokenBasedEventCount = 0;

        foreach ($events as $event) {
            $eventCount++;

            if (! $event->isTokenBasedCall) {
                continue;
            }

            $tokenBasedEventCount++;
            $inputTokens += $event->inputTokens;
            $outputTokens += $event->outputTokens;
            $cacheReadTokens += $event->cacheReadTokens;
            $usageCostCents += $event->chargedCents;
        }

        $averageContextSize = $tokenBasedEventCount > 0
            ? (int) round($inputTokens / $tokenBasedEventCount)
            : 0;

        return new UsageSummary(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cacheReadTokens: $cacheReadTokens,
            averageContextSize: $averageContextSize,
            usageCostCents: (int) round($usageCostCents),
            eventCount: $eventCount,
            tokenBasedEventCount: $tokenBasedEventCount,
        );
    }
}
