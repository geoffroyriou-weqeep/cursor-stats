<?php

namespace App\Services\Cursor;

enum DatePreset: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';

    public function label(): string
    {
        return match ($this) {
            self::Today => 'Aujourd\'hui',
            self::Yesterday => 'Hier',
            self::Last7Days => '7 derniers jours',
        };
    }
}
