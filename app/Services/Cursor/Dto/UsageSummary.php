<?php

namespace App\Services\Cursor\Dto;

final readonly class UsageSummary
{
    public function __construct(
        public int $inputTokens,
        public int $outputTokens,
        public int $cacheReadTokens,
        public int $averageContextSize,
        public int $usageCostCents,
        public int $eventCount,
    ) {}

    public function formattedCost(): string
    {
        $euros = $this->usageCostCents / 100;

        return number_format($euros, 2, ',', ' ').' €';
    }

    public function formattedTokens(int $tokens): string
    {
        return number_format($tokens, 0, ',', ' ');
    }
}
