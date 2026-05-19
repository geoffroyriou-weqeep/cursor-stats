<?php

namespace App\Services\Cursor\Contracts;

use App\Services\Cursor\ReportingPeriod;
use App\Services\Cursor\UsageEventDto;

interface CursorUsageClient
{
    /**
     * @return list<UsageEventDto>
     */
    public function fetchUsageEvents(ReportingPeriod $period): array;
}
