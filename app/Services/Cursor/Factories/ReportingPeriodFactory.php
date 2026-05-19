<?php

namespace App\Services\Cursor\Factories;

use App\Services\Cursor\Dto\ReportingPeriod;
use App\Services\Cursor\Enums\DatePreset;
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

        return new ReportingPeriod(
            startMs: (int) $start->valueOf(),
            endMs: (int) $end->valueOf(),
            label: DatePreset::Last7Days->label().' ('.$this->formatRangeLabel($start, $end).')',
        );
    }

    public function forRange(CarbonImmutable $startDay, CarbonImmutable $endDay): ReportingPeriod
    {
        $timezone = config('cursor_stats.timezone');
        $start = $startDay->timezone($timezone)->startOfDay();
        $end = $endDay->timezone($timezone)->endOfDay();

        return new ReportingPeriod(
            startMs: (int) $start->valueOf(),
            endMs: (int) $end->valueOf(),
            label: 'Personnalisé ('.$this->formatRangeLabel($start, $end).')',
        );
    }

    private function formatRangeLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($start->isSameDay($end)) {
            return $start->locale('fr')->isoFormat('D MMMM YYYY');
        }

        if ($start->isSameMonth($end) && $start->isSameYear($end)) {
            return $start->locale('fr')->isoFormat('D').'–'.$end->locale('fr')->isoFormat('D MMMM YYYY');
        }

        return $start->locale('fr')->isoFormat('D MMMM').' – '.$end->locale('fr')->isoFormat('D MMMM YYYY');
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
