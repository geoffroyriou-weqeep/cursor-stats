<?php

namespace App\Http\Controllers;

use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\DatePreset;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\ReportingPeriodFactory;
use App\Services\Cursor\UsageSummaryBuilder;
use Illuminate\View\View;

class UsageDashboardController extends Controller
{
    public function __invoke(
        ReportingPeriodFactory $periodFactory,
        CursorUsageClient $usageClient,
        UsageSummaryBuilder $summaryBuilder,
    ): View {
        try {
            $period = $periodFactory->forPreset(DatePreset::Today);
            $events = $usageClient->fetchUsageEvents($period);
            $summary = $summaryBuilder->build($events);

            return view('usage.dashboard', [
                'period' => $period,
                'summary' => $summary,
            ]);
        } catch (CursorSessionUnavailableException $exception) {
            return view('usage.auth-failure', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
