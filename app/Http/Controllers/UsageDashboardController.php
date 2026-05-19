<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsageDashboardRequest;
use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\ReportingPeriodFactory;
use App\Services\Cursor\SqliteSessionCredentialResolver;
use App\Services\Cursor\UsageSummaryBuilder;
use Illuminate\View\View;

class UsageDashboardController extends Controller
{
    public function __invoke(
        UsageDashboardRequest $request,
        ReportingPeriodFactory $periodFactory,
        CursorUsageClient $usageClient,
        UsageSummaryBuilder $summaryBuilder,
    ): View {
        try {
            $preset = $request->preset();
            $period = $periodFactory->forPreset($preset);
            $events = $usageClient->fetchUsageEvents($period);
            $summary = $summaryBuilder->build($events);

            return view('usage.dashboard', [
                'period' => $period,
                'preset' => $preset,
                'summary' => $summary,
            ]);
        } catch (CursorSessionUnavailableException $exception) {
            return view('usage.auth-failure', [
                'message' => $exception->getMessage(),
                'sqlitePath' => SqliteSessionCredentialResolver::databasePath(),
            ]);
        }
    }
}
