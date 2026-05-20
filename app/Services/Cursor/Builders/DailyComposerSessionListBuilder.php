<?php

namespace App\Services\Cursor\Builders;

use App\Services\Cursor\Dto\ComposerSessionDto;
use App\Services\Cursor\Dto\ReportingPeriod;
use Carbon\CarbonImmutable;

final class DailyComposerSessionListBuilder
{
    /**
     * @param  list<ComposerSessionDto>  $sessions
     * @return list<ComposerSessionDto>
     */
    public function build(
        array $sessions,
        ReportingPeriod $dailyPeriod,
        CarbonImmutable $now,
    ): array {
        $nowMs = (int) $now->valueOf();
        $matching = [];

        foreach ($sessions as $session) {
            $sessionEndMs = $session->lastUpdatedAtMs ?? $nowMs;

            if ($session->createdAtMs <= $dailyPeriod->endMs
                && $sessionEndMs >= $dailyPeriod->startMs) {
                $matching[] = $session;
            }
        }

        usort($matching, function (ComposerSessionDto $a, ComposerSessionDto $b): int {
            $aSort = $a->lastUpdatedAtMs ?? $a->createdAtMs;
            $bSort = $b->lastUpdatedAtMs ?? $b->createdAtMs;

            return $bSort <=> $aSort;
        });

        return $matching;
    }
}
