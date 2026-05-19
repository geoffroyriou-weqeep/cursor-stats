<?php

namespace App\Services\Cursor;

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

        foreach ($events as $event) {
            $eventCount++;

            if (! $event->isTokenBasedCall) {
                continue;
            }

            $inputTokens += $event->inputTokens;
            $outputTokens += $event->outputTokens;
            $cacheReadTokens += $event->cacheReadTokens;
            $usageCostCents += $event->chargedCents;
        }

        return new UsageSummary(
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
            cacheReadTokens: $cacheReadTokens,
            usageCostCents: (int) round($usageCostCents),
            eventCount: $eventCount,
        );
    }
}
