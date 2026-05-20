<?php

namespace App\Services\Cursor\Attributors;

use App\Services\Cursor\Dto\AttributionResult;
use App\Services\Cursor\Dto\ComposerSessionDto;
use App\Services\Cursor\Dto\ReportingPeriod;
use App\Services\Cursor\Dto\UsageEventDto;
use Carbon\CarbonImmutable;

final class UsageEventAttributor
{
    /**
     * @param  list<UsageEventDto>  $events
     * @param  list<ComposerSessionDto>  $sessions
     */
    public function attribute(
        array $events,
        array $sessions,
        ReportingPeriod $dailyPeriod,
        CarbonImmutable $now,
    ): AttributionResult {
        $nowMs = (int) $now->valueOf();
        $periodEndMs = $dailyPeriod->endMs;
        $byComposerId = [];
        $unassigned = [];

        foreach ($events as $event) {
            $candidates = [];

            foreach ($sessions as $session) {
                $sessionEndMs = $session->lastUpdatedAtMs ?? $periodEndMs ?? $nowMs;

                if ($session->createdAtMs <= $event->timestamp
                    && $event->timestamp <= $sessionEndMs) {
                    $candidates[] = $session;
                }
            }

            if ($candidates === []) {
                $unassigned[] = $event;

                continue;
            }

            $winner = $candidates[0];

            foreach ($candidates as $candidate) {
                if ($candidate->createdAtMs > $winner->createdAtMs) {
                    $winner = $candidate;
                } elseif ($candidate->createdAtMs === $winner->createdAtMs
                    && $candidate->composerId > $winner->composerId) {
                    $winner = $candidate;
                }
            }

            $byComposerId[$winner->composerId] ??= [];
            $byComposerId[$winner->composerId][] = $event;
        }

        return new AttributionResult($byComposerId, $unassigned);
    }
}
