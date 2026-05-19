<?php

namespace App\Services\Cursor;

final readonly class ReportingPeriod
{
    public function __construct(
        public int $startMs,
        public int $endMs,
        public string $label,
    ) {}
}
