<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsageDashboardRequest;
use App\Services\Cursor\Attributors\UsageEventAttributor;
use App\Services\Cursor\Builders\DailyComposerSessionListBuilder;
use App\Services\Cursor\Builders\UsageSummaryBuilder;
use App\Services\Cursor\Contracts\ComposerSessionRegistry;
use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\Factories\ReportingPeriodFactory;
use App\Services\Cursor\Resolvers\SqliteSessionCredentialResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsageDashboardController extends Controller
{
    public function __invoke(
        UsageDashboardRequest $request,
        ReportingPeriodFactory $periodFactory,
        CursorUsageClient $usageClient,
        UsageSummaryBuilder $summaryBuilder,
        ComposerSessionRegistry $sessionRegistry,
        DailyComposerSessionListBuilder $dailySessionListBuilder,
        UsageEventAttributor $attributor,
    ): View|RedirectResponse {
        try {
            $now = CarbonImmutable::now(config('cursor_stats.timezone'));
            $period = $request->reportingPeriod($periodFactory);
            $dailyPeriod = $periodFactory->forToday($now);

            $events = $usageClient->fetchUsageEvents($period);
            $summary = $summaryBuilder->build($events);

            $dailyEvents = $usageClient->fetchUsageEvents($dailyPeriod);
            $allSessions = $sessionRegistry->listAll();
            $dailySessions = $dailySessionListBuilder->build($allSessions, $dailyPeriod, $now);
            $dailySessionIds = array_map(
                fn ($session) => $session->composerId,
                $dailySessions,
            );

            if (! $request->isComposerValidForDailyList($dailySessionIds)) {
                return redirect()->to($request->urlWithoutComposer());
            }

            $attribution = $attributor->attribute($dailyEvents, $dailySessions, $dailyPeriod, $now);
            $selectedComposerId = $request->composerId();
            $selectedSession = null;
            $selectedSummary = null;

            if ($selectedComposerId !== null) {
                foreach ($dailySessions as $session) {
                    if ($session->composerId === $selectedComposerId) {
                        $selectedSession = $session;
                        break;
                    }
                }

                $selectedSummary = $summaryBuilder->build(
                    $attribution->eventsFor($selectedComposerId),
                );
            }

            return view('usage.dashboard', [
                'period' => $period,
                'dailyPeriod' => $dailyPeriod,
                'preset' => $request->usesCustomRange() ? null : $request->preset(),
                'isCustomRange' => $request->usesCustomRange(),
                'customFrom' => $request->customFrom(),
                'customTo' => $request->customTo(),
                'summary' => $summary,
                'dailySessions' => $dailySessions,
                'selectedComposerId' => $selectedComposerId,
                'selectedSession' => $selectedSession,
                'selectedSummary' => $selectedSummary,
                'unattributedEventCount' => count($attribution->unassigned),
                'dashboardRequest' => $request,
            ]);
        } catch (CursorSessionUnavailableException $exception) {
            return view('usage.auth-failure', [
                'message' => $exception->getMessage(),
                'sqlitePath' => SqliteSessionCredentialResolver::databasePath(),
            ]);
        }
    }
}
