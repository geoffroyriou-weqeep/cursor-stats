<?php

namespace App\Services\Cursor\Dto;

final readonly class UsageEventDto
{
    public function __construct(
        public int $timestamp,
        public bool $isTokenBasedCall,
        public int $inputTokens,
        public int $outputTokens,
        public int $cacheReadTokens,
        public float $chargedCents,
    ) {}
}
