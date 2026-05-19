<?php

namespace App\Services\Cursor\Contracts;

use App\Services\Cursor\Dto\ReportingPeriod;
use App\Services\Cursor\Dto\UsageEventDto;

interface CursorUsageClient
{
    /**
     * @return list<UsageEventDto>
     */
    public function fetchUsageEvents(ReportingPeriod $period): array;
}
