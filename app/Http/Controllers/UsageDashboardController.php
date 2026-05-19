<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsageDashboardRequest;
use App\Services\Cursor\Builders\UsageSummaryBuilder;
use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\Factories\ReportingPeriodFactory;
use App\Services\Cursor\Resolvers\SqliteSessionCredentialResolver;
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
            $period = $request->reportingPeriod($periodFactory);
            $events = $usageClient->fetchUsageEvents($period);
            $summary = $summaryBuilder->build($events);

            return view('usage.dashboard', [
                'period' => $period,
                'preset' => $request->usesCustomRange() ? null : $request->preset(),
                'isCustomRange' => $request->usesCustomRange(),
                'customFrom' => $request->customFrom(),
                'customTo' => $request->customTo(),
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
