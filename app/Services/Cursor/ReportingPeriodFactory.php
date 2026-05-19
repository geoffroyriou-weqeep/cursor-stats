<?php

namespace App\Services\Cursor;

use Carbon\CarbonImmutable;

final class ReportingPeriodFactory
{
    public function forPreset(DatePreset $preset, ?CarbonImmutable $now = null): ReportingPeriod
    {
        return match ($preset) {
            DatePreset::Today => $this->forToday($now),
        };
    }

    public function forToday(?CarbonImmutable $now = null): ReportingPeriod
    {
        $timezone = config('cursor_stats.timezone');
        $now ??= CarbonImmutable::now($timezone);

        $start = $now->startOfDay();
        $end = $now->endOfDay();

        return new ReportingPeriod(
            startMs: (int) $start->valueOf(),
            endMs: (int) $end->valueOf(),
            label: 'Aujourd\'hui',
        );
    }
}
