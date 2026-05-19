# PRD — Cursor Stats Dashboard

**Status:** ready for implementation  
**Domain glossary:** [`CONTEXT.md`](../../CONTEXT.md)  
**ADR:** [`0001-usage-via-dashboard-api`](../adr/0001-usage-via-dashboard-api.md)

---

## Problem Statement

En plan Cursor Pro+, il n’y a pas d’accès à l’Admin API Enterprise pour récupérer programmatiquement la consommation de tokens (input, output, cache read) et le coût associé. Le dashboard officiel Cursor affiche ces informations, mais il faut s’y connecter manuellement et on ne voit pas facilement un **Usage Summary** agrégé pour une **Date Range** choisie (aujourd’hui par défaut, minuit à minuit en **Reporting Timezone**).

L’utilisateur veut un outil **local** (Laravel Herd) qu’il recharge à la main pour consulter ses stats personnelles, sans job ni mise à jour automatique.

---

## Solution

Une application Laravel locale affiche une page unique avec :

1. Sélection de période via **Date Preset** (Aujourd’hui, Hier, 7 derniers jours) ou **Date Range** personnalisée (deux dates).
2. Un **Usage Summary** : totaux de tokens (input, output, cache read) pour la période.
3. Un **Montant réel** sous les stats : **Usage Cost** = somme des `chargedCents` des Usage Events token-based, en euros.
4. En cas d’**Auth Failure** : page dédiée avec instructions (pas de stats à zéro).

Les données proviennent de l’**Usage Data Source** dashboard (API non officielle), via **Session Credential** hybride (SQLite Cursor → fallback cookie).

---

## User Stories

1. As a Cursor Pro+ user, I want to open a local dashboard in my browser, so that I can see my token usage without using the Cursor web UI.
2. As a Cursor Pro+ user, I want today’s usage shown by default (Daily View), so that I immediately see how much I’ve consumed this calendar day.
3. As a Cursor Pro+ user, I want “today” to follow my local timezone (default Europe/Paris), so that midnight boundaries match how I think about my day.
4. As a Cursor Pro+ user, I want to switch to yesterday via a Date Preset, so that I can review the previous day quickly.
5. As a Cursor Pro+ user, I want a “last 7 days” Date Preset, so that I can see weekly totals without picking dates manually.
6. As a Cursor Pro+ user, I want a custom Date Range with start and end dates, so that I can analyze arbitrary periods.
7. As a Cursor Pro+ user, I want to see total input tokens for the selected period, so that I understand input volume.
8. As a Cursor Pro+ user, I want to see total output tokens for the selected period, so that I understand generation volume.
9. As a Cursor Pro+ user, I want to see total cache read tokens for the selected period, so that I understand cache efficiency.
10. As a Cursor Pro+ user, I want a single “Montant réel” line below the token stats, so that I see total charged cost for the period in euros.
11. As a Cursor Pro+ user, I want the dashboard to use my Cursor session automatically when possible, so that I don’t copy cookies on every visit.
12. As a Cursor Pro+ user, I want to configure a fallback session cookie in `.env`, so that the dashboard still works if SQLite read fails.
13. As a Cursor Pro+ user, I want a clear error page when authentication fails, so that I know how to fix it (open Cursor, refresh token, set cookie).
14. As a Cursor Pro+ user, I want to refresh the page to update stats, so that I control when data is fetched (no background jobs).
15. As a Cursor Pro+ user, I want my session credentials to stay on my machine, so that I don’t expose my Cursor account to a remote server.
16. As a Cursor Pro+ user, I want numbers formatted readably (thousands separators), so that large token counts are easy to scan.
17. As a Cursor Pro+ user, I want the UI to be simple (Tailwind, no charts), so that the dashboard loads fast and stays maintainable.
18. As a Cursor Pro+ user, I want only my own Usage Events (implicit via session), so that I never see teammates’ data on a personal Pro+ account.
19. As a developer, I want deep modules with small interfaces, so that API fragility and aggregation logic are testable in isolation.
20. As a developer, I want Laravel conventions (config, services, form request, controller, Blade), so that the codebase stays familiar and maintainable.
21. As a developer, I want configuration via `.env` for timezone and optional cookie, so that secrets aren’t hardcoded.
22. As a developer, I want non-token-based Usage Events excluded from token totals, so that aggregates aren’t skewed.
23. As a developer, I want the usage client to paginate through all Usage Events for a Date Range on each page load, so that totals are complete.
24. As a developer, I want domain exceptions for auth failures, so that the HTTP layer can render the Auth Failure view consistently.
25. As a future Enterprise user, I want the Usage Summary contract to stay stable, so that an Admin API adapter could be added later without rewriting the UI.

---

## Implementation Decisions

### Architectural shape

- **Local Deployment** only: Laravel Herd on the developer’s Mac; no production deploy in MVP.
- **No database persistence** for usage data: each request fetches from Cursor and aggregates in memory.
- **No queues, no schedulers, no WebSockets**: reload page = refresh data.
- Vocabulary and boundaries follow [`CONTEXT.md`](../../CONTEXT.md) and [ADR 0001](../adr/0001-usage-via-dashboard-api.md).

### Laravel layout (conventions)

Follow standard Laravel layering:

| Layer | Responsibility |
|-------|----------------|
| **Config** | `config/cursor_stats.php` — timezone, SQLite path override, cookie fallback, API base URL, page size |
| **`.env`** | `CURSOR_STATS_TIMEZONE`, `CURSOR_SESSION_COOKIE` (optional), paths if needed |
| **Service container** | Bind interfaces in `AppServiceProvider` |
| **Form Request** | Validate Date Preset / custom range query params |
| **Controller** | Thin: resolve period → fetch summary → return view or auth error view |
| **Blade + Tailwind** | Dashboard and auth-failure templates; existing Vite/Tailwind stack |
| **Exceptions** | Domain exception for missing/invalid session; render dedicated view |

No custom `app/Modules/` package structure — use `App\Services\Cursor\` (or `App\Cursor\`) namespaces idiomatic to Laravel.

### Deep modules (interfaces)

#### 1. `SessionCredentialResolver` (interface)

**Purpose:** Hide hybrid auth (SQLite → cookie fallback).

**Contract:**

- `resolve(): SessionCredential` — value object with cookie header value or equivalent for HTTP client.
- Throws `CursorSessionUnavailableException` when neither source works.

**Implementations (internal):**

- `SqliteSessionCredentialResolver` — read `cursorAuth/accessToken` from Cursor `state.vscdb`; build `WorkosCursorSessionToken` if needed per reverse-engineered format.
- `EnvSessionCredentialResolver` — read `CURSOR_SESSION_COOKIE` from config.
- `CompositeSessionCredentialResolver` — try SQLite first, then env.

**Why deep:** SQLite paths, JWT expiry, cookie formatting, and fallback order are volatile; controller must not know them.

#### 2. `ReportingPeriodFactory` (or `ReportingPeriod`)

**Purpose:** Convert Date Preset / custom dates + **Reporting Timezone** into inclusive millisecond bounds for the API.

**Contract:**

- `forPreset(DatePreset $preset, ?CarbonImmutable $now = null): ReportingPeriod` — returns `startMs`, `endMs`, human label.
- `forRange(CarbonImmutable $start, CarbonImmutable $end, Timezone $tz): ReportingPeriod` — inclusive calendar dates in timezone.

**Presets:** `today`, `yesterday`, `last_7_days` (7 calendar days ending today inclusive).

**Why deep:** Timezone math and API string millisecond format are easy to get wrong.

#### 3. `CursorUsageClient` (interface)

**Purpose:** HTTP integration with **Usage Data Source**; pagination; map DTOs.

**Contract:**

- `fetchUsageEvents(ReportingPeriod $period): iterable<UsageEventDto>` — loops pages until all events collected for period.
- Uses `SessionCredential` on each request.
- POST body: `startDate`, `endDate` as strings (ms), `page`, `pageSize`.
- Headers: session cookie, `Origin: https://cursor.com`, `Content-Type: application/json`.

**DTO `UsageEventDto`:** `timestamp`, `isTokenBasedCall`, `inputTokens`, `outputTokens`, `cacheReadTokens`, `cacheWriteTokens` (optional), `chargedCents`.

**Why deep:** Undocumented API, pagination, HTTP errors, response shape drift.

#### 4. `UsageSummaryBuilder`

**Purpose:** Pure aggregation — no HTTP.

**Contract:**

- `build(iterable<UsageEventDto> $events): UsageSummary` — value object with `inputTokens`, `outputTokens`, `cacheReadTokens`, `usageCostCents` (int), `eventCount`.

**Rules:**

- Sum tokens only when `isTokenBasedCall` is true and `tokenUsage` present.
- **Usage Cost:** sum `chargedCents` (float from API → round to cents integer for display).
- Ignore non-token events for token totals; still include their `chargedCents` in Usage Cost only if token-based (match rule: token-based only for cost too, per CONTEXT).

**Why deep:** Business rules for aggregates are the core domain; must be unit-tested without HTTP.

#### 5. `UsageDashboardController` (thin)

**Flow:**

1. Validate query via Form Request (preset or `from`/`to` dates).
2. `ReportingPeriodFactory` → period.
3. `SessionCredentialResolver` → credential.
4. `CursorUsageClient` → events.
5. `UsageSummaryBuilder` → summary.
6. Return `usage.dashboard` view with summary + period label.

On `CursorSessionUnavailableException` → `usage.auth-failure` view (no redirect loop).

### API contract (external, unofficial)

- **Endpoint:** `POST https://cursor.com/api/dashboard/get-filtered-usage-events`
- **Auth:** `Cookie: WorkosCursorSessionToken=...`
- **Response:** `usageEventsDisplay[]`, `totalUsageEventsCount`, pagination implied by page/size
- **Fields used:** `timestamp`, `isTokenBasedCall`, `tokenUsage.{inputTokens,outputTokens,cacheReadTokens,cacheWriteTokens}`, `chargedCents`
- **Not used in MVP:** per-event UI, model name, `usageBasedCosts`, team filters

### Configuration defaults

- `CURSOR_STATS_TIMEZONE=Europe/Paris`
- `CURSOR_SESSION_COOKIE=` (empty = SQLite only)
- Page size: 100 (configurable)
- SQLite path default: macOS Cursor `globalStorage/state.vscdb`

### UI behavior

- Single route `GET /` (or `/usage`).
- Preset links/buttons set query string; custom range via two `<input type="date">` + submit.
- Display: three stat rows + separator + **Montant réel** formatted `X,XX €`.
- Labels: « Input », « Output », « Cache read » (per CONTEXT).
- French UI copy acceptable (matches conversation).

### Error handling

- **Auth Failure view:** explain SQLite path, Cursor must be installed/logged in, how to set `CURSOR_SESSION_COOKIE`, link to dashboard usage page.
- HTTP 401/403 from API → treat as auth failure.
- Other HTTP errors → generic error page with message (not fake zeros).

---

## Testing Decisions

### What makes a good test

- Test **observable behavior** of modules via public interfaces — inputs and outputs, not private methods.
- No HTTP in `UsageSummaryBuilder` or `ReportingPeriodFactory` tests.
- HTTP client tests use `Http::fake()` at the `CursorUsageClient` integration level only.
- Do not test Blade markup beyond optional one feature test for 200 + key text.

### Modules to test (recommended)

| Module | Test type | Rationale |
|--------|-----------|-----------|
| `UsageSummaryBuilder` | Unit (Pest) | Core business rules; pure functions |
| `ReportingPeriodFactory` | Unit (Pest) | Timezone edge cases (DST, midnight) |
| `CompositeSessionCredentialResolver` | Unit with temp files / env | Fallback order |
| `CursorUsageClient` | Feature with `Http::fake()` | Pagination + mapping |
| Controller | Optional 1 feature test | Auth failure renders correct view |

### Prior art

- Greenfield Laravel 13 + Pest skeleton (`tests/Feature/ExampleTest.php`, `tests/Pest.php`).
- Follow Pest conventions already in repo.

### Not testing in MVP

- Real SQLite reads against user’s Cursor install (manual QA).
- Live calls to `cursor.com` in CI.
- Visual/regression tests.

---

## Out of Scope

- Admin API / Enterprise `api.cursor.com` integration
- Analytics API
- Per-event list or drill-down table
- Charts or historical trends UI
- Cost breakdown per token type (API does not provide it)
- Automatic refresh, polling, queues, caching layer
- Multi-user / team views
- Hosted deployment or authentication for the Laravel app itself
- Mobile layout polish beyond responsive Tailwind basics
- `cacheWriteTokens` column unless API starts returning it regularly
- Export CSV
- Comparing periods side-by-side
- Database storage of historical summaries
- Rate-limit handling beyond simple error surfacing

---

## Further Notes

- **Pro+ billing semantics:** `chargedCents` on `INCLUDED_IN_PRO_PLUS` events reflects valued usage, not necessarily out-of-pocket spend; UI may add a short disclaimer under **Montant réel**.
- **API stability:** monitor [ADR 0001](../adr/0001-usage-via-dashboard-api.md); breaking changes only touch `CursorUsageClient` + DTO mapping.
- **Pagination performance:** long Date Ranges may require many sequential requests on page reload; acceptable for MVP; consider max range later if slow.
- **Linux/Windows:** SQLite path differs; config override documents path — macOS is primary dev environment (Herd).
- **Security:** add `.env` cursor vars to `.env.example` with empty values; document in README when implementation lands.
- **Implementation order suggestion:** `UsageSummaryBuilder` → `ReportingPeriodFactory` → `CursorUsageClient` (fake HTTP) → `SessionCredentialResolver` → controller + views.
