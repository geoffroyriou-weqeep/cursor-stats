<?php

namespace App\Services\Cursor\Dto;

final readonly class AttributionResult
{
    /**
     * @param  array<string, list<UsageEventDto>>  $byComposerId
     * @param  list<UsageEventDto>  $unassigned
     */
    public function __construct(
        public array $byComposerId,
        public array $unassigned,
    ) {}

    /**
     * @return list<UsageEventDto>
     */
    public function eventsFor(string $composerId): array
    {
        return $this->byComposerId[$composerId] ?? [];
    }
}
