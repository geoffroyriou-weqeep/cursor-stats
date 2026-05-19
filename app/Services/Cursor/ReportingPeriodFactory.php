<?php

namespace App\Services\Cursor;

use Carbon\CarbonImmutable;

final class ReportingPeriodFactory
{
    public function forPreset(DatePreset $preset, ?CarbonImmutable $now = null): ReportingPeriod
    {
        return match ($preset) {
            DatePreset::Today => $this->forToday($now),
            DatePreset::Yesterday => $this->forYesterday($now),
            DatePreset::Last7Days => $this->forLast7Days($now),
        };
    }

    public function forToday(?CarbonImmutable $now = null): ReportingPeriod
    {
        $now = $this->resolveNow($now);

        return $this->forCalendarDay($now, DatePreset::Today->label());
    }

    public function forYesterday(?CarbonImmutable $now = null): ReportingPeriod
    {
        $now = $this->resolveNow($now);

        return $this->forCalendarDay($now->subDay(), DatePreset::Yesterday->label());
    }

    public function forLast7Days(?CarbonImmutable $now = null): ReportingPeriod
    {
        $now = $this->resolveNow($now);
        $start = $now->subDays(6)->startOfDay();
        $end = $now->endOfDay();

        $rangeLabel = $start->isSameMonth($end)
            ? $start->locale('fr')->isoFormat('D').'–'.$end->locale('fr')->isoFormat('D MMMM YYYY')
            : $start->locale('fr')->isoFormat('D MMMM').' – '.$end->locale('fr')->isoFormat('D MMMM YYYY');

        return new ReportingPeriod(
            startMs: (int) $start->valueOf(),
            endMs: (int) $end->valueOf(),
            label: DatePreset::Last7Days->label().' ('.$rangeLabel.')',
        );
    }

    private function forCalendarDay(CarbonImmutable $day, string $label): ReportingPeriod
    {
        $start = $day->startOfDay();
        $end = $day->endOfDay();

        return new ReportingPeriod(
            startMs: (int) $start->valueOf(),
            endMs: (int) $end->valueOf(),
            label: $label,
        );
    }

    private function resolveNow(?CarbonImmutable $now): CarbonImmutable
    {
        $timezone = config('cursor_stats.timezone');

        return $now ?? CarbonImmutable::now($timezone);
    }
}
