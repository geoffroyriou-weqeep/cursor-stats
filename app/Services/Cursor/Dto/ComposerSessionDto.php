<?php

namespace App\Services\Cursor\Dto;

final readonly class ComposerSessionDto
{
    public function __construct(
        public string $composerId,
        public ?string $name,
        public int $createdAtMs,
        public ?int $lastUpdatedAtMs,
        public ?string $workspacePath,
        public ?string $workspaceHash,
        public string $unifiedMode,
    ) {}

    public function displayTitle(): string
    {
        if (is_string($this->name) && trim($this->name) !== '') {
            return trim($this->name);
        }

        return substr($this->composerId, 0, 8).'…';
    }
}
