<?php

namespace App\Services\Cursor;

use App\Services\Cursor\Contracts\CursorUsageClient;
use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use Illuminate\Support\Facades\Http;

final class HttpCursorUsageClient implements CursorUsageClient
{
    public function __construct(
        private readonly SessionCredentialResolver $credentialResolver,
    ) {}

    /**
     * @return list<UsageEventDto>
     */
    public function fetchUsageEvents(ReportingPeriod $period): array
    {
        $credential = $this->credentialResolver->resolve();
        $pageSize = config('cursor_stats.page_size');
        $baseUrl = rtrim((string) config('cursor_stats.api_base_url'), '/');
        $url = $baseUrl.'/api/dashboard/get-filtered-usage-events';

        $events = [];
        $page = 1;
        $totalCount = null;

        do {
            $response = Http::withHeaders([
                'Cookie' => $credential->cookieHeader,
                'Origin' => 'https://cursor.com',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'startDate' => (string) $period->startMs,
                'endDate' => (string) $period->endMs,
                'page' => $page,
                'pageSize' => $pageSize,
            ]);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new CursorSessionUnavailableException(
                    'Cursor rejected the session cookie (HTTP '.$response->status().').',
                );
            }

            $response->throw();

            $body = $response->json();
            $totalCount ??= (int) ($body['totalUsageEventsCount'] ?? 0);
            $pageEvents = $body['usageEventsDisplay'] ?? [];

            foreach ($pageEvents as $rawEvent) {
                $events[] = $this->mapEvent($rawEvent);
            }

            $page++;
        } while (count($events) < $totalCount && count($pageEvents) > 0);

        return $events;
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function mapEvent(array $raw): UsageEventDto
    {
        $tokenUsage = is_array($raw['tokenUsage'] ?? null) ? $raw['tokenUsage'] : [];
        $isTokenBased = (bool) ($raw['isTokenBasedCall'] ?? false);

        return new UsageEventDto(
            timestamp: (int) ($raw['timestamp'] ?? 0),
            isTokenBasedCall: $isTokenBased,
            inputTokens: $isTokenBased ? (int) ($tokenUsage['inputTokens'] ?? 0) : 0,
            outputTokens: $isTokenBased ? (int) ($tokenUsage['outputTokens'] ?? 0) : 0,
            cacheReadTokens: $isTokenBased ? (int) ($tokenUsage['cacheReadTokens'] ?? 0) : 0,
            chargedCents: (float) ($raw['chargedCents'] ?? 0),
        );
    }
}
