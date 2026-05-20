# PRD — Session Usage Breakdown (stats par Composer Session)

**Status:** ready for implementation  
**Domain glossary:** [`CONTEXT.md`](../../CONTEXT.md)  
**ADR:** [`0001-usage-via-dashboard-api`](../adr/0001-usage-via-dashboard-api.md)  
**Prior PRD:** [`cursor-stats-dashboard`](cursor-stats-dashboard.md)

---

## Problem Statement

En plan Cursor Pro+, le dashboard local **cursor-stats** affiche déjà un **Usage Summary** agrégé pour une **Date Range** choisie (tokens, **Contexte moyen**, **Montant réel**). L’**Usage Data Source** ne fournit pas de `composerId` sur les **Usage Events** : impossible de savoir quelle part de la consommation correspond à quel fil Composer / fenêtre Agent.

L’utilisateur veut, sur la même page, consulter les **mêmes métriques** mais pour un **Composer Session** précis « du jour », sans quitter l’outil ni persister les données en base. L’attribution reste **heuristique** (fenêtres temporelles locales), acceptable pour un usage personnel.

---

## Solution

Conserver l’**Usage Summary** global inchangé (gauche, **Date Range** existante). Ajouter à droite une zone **Session Usage Breakdown** :

1. **Daily Composer Session List** — fils dont la fenêtre intersecte aujourd’hui (**Daily View**, **Reporting Timezone**), triés par `lastUpdatedAt` décroissant, avec **Composer Session Title** et **Composer Workspace Path**.
2. Sélection d’un fil → **Selected Session Summary** (mêmes cartes que le global, sur les **Usage Events** du jour attribués à ce `composerId`).
3. Sous le résumé du fil sélectionné : **Token-Based Event Count** et **Unattributed Event Count** (events du jour non rattachés à un fil).
4. Mise en page **50 / 50** sur grand écran ; empilée sur mobile. Sélecteur de **Date Range** pleine largeur au-dessus.

Les **Usage Events** viennent uniquement de l’**Usage Data Source** ; les métadonnées de fil viennent du **Composer Session Registry** (`composer.composerHeaders` dans le même SQLite que l’auth, chemin configurable).

---

## User Stories

### Usage global (inchangé)

1. As a Cursor Pro+ user, I want the existing **Usage Summary** and **Date Range** controls to behave exactly as today, so that my current workflow is not disrupted.
2. As a Cursor Pro+ user, I want the period selector to stay full-width above both columns, so that I change the global period in one place.

### Liste des fils du jour

3. As a Cursor Pro+ user, I want to see all **Composer Session** that are active today (window intersects today), so that I can pick the Agent thread I care about.
4. As a Cursor Pro+ user, I want each list entry to show the **Composer Session Title** and project path, so that I can distinguish similarly named threads across repos.
5. As a Cursor Pro+ user, I want the list sorted by most recent activity (`lastUpdatedAt`), so that my current thread is near the top.
6. As a Cursor Pro+ user, I want no thread pre-selected on first visit, so that I explicitly choose what to inspect.
7. As a Cursor Pro+ user, I want the session column to always reflect **today** in my **Reporting Timezone**, even when the global summary shows yesterday or last 7 days, so that I can compare period totals vs. today’s per-thread usage.

### Sélection et URL

8. As a Cursor Pro+ user, I want selecting a thread to set `?composer=<composerId>` in the URL, so that reload keeps my selection.
9. As a Cursor Pro+ user, I want an invalid or stale `composer` query param removed via redirect, so that I never see a broken selection state.
10. As a Cursor Pro+ user, I want period preset links to preserve or compose cleanly with `composer` when applicable, so that navigation stays predictable.

### Résumé par fil

11. As a Cursor Pro+ user, I want the same token cards and **Montant réel** for the selected thread as on the global summary, so that metrics are comparable.
12. As a Cursor Pro+ user, I want a **Token-Based Event Count** under the selected thread summary, so that the count matches the token/cost cards (unlike the global `eventCount` which includes all event types).
13. As a Cursor Pro+ user, I want to see how many of today’s **Usage Events** could not be attributed to any thread, but only after I select a thread, so that I am aware of gaps without cluttering the empty state.
14. As a Cursor Pro+ user, I want attributed headless events to follow the same rules as other events, so that sub-agent usage is not artificially excluded.

### Données et confiance

15. As a Cursor Pro+ user, I want all billing numbers to still come from the API only, so that I trust token and cost totals.
16. As a Cursor Pro+ user, I want **Local Deployment** preserved (no remote DB, credentials stay on machine), so that my Cursor account data does not leave the Mac.
17. As a Cursor Pro+ user, I want the UI in French and consistent with the existing Tailwind dashboard, so that the product feels cohesive.
18. As a Cursor Pro+ user, I want a side-by-side layout on desktop, so that I see global and per-thread stats at a glance.

### Développeur

19. As a developer, I want **Estimated Attribution** isolated in a deep module with a small interface, so that tie-break and window rules are unit-testable without HTTP or SQLite fixtures for the full Cursor install.
20. As a developer, I want the **Composer Session Registry** behind its own interface, so that parsing `composer.composerHeaders` can be tested with a minimal SQLite fixture.
21. As a developer, I want the HTTP usage client unchanged in responsibility (API only), so that ADR 0001’s seam stays intact.
22. As a developer, I want to reuse **UsageSummaryBuilder** for both global and per-thread aggregates, so that aggregation rules stay single-sourced.
23. As a developer, I want the controller to orchestrate two periods (global **Date Range** + **Daily View** for sessions) without embedding attribution logic, so that the HTTP layer stays thin.

---

## Implementation Decisions

### Architectural shape

- **Local Deployment** only; no persistence of usage or attribution results.
- **Two-column dashboard** below the period selector: global **Usage Summary** (left), **Session Usage Breakdown** (right); stack on small viewports.
- Vocabulary and behaviour follow [`CONTEXT.md`](../../CONTEXT.md). Do not re-litigate ADR 0001 (**Usage Data Source** via unofficial dashboard API).

### Data flow (canonical)

1. Resolve global **ReportingPeriod** from request (**Date Preset** or custom range).
2. Resolve **Daily View** period for session features (today in **Reporting Timezone**), independent of global range.
3. **CursorUsageClient** → fetch **Usage Events** for global period (existing).
4. **CursorUsageClient** → fetch **Usage Events** for daily period (second call; acceptable for MVP reload model).
5. **ComposerSessionRegistry** → load all **Composer Session** metadata from SQLite; filter to **Daily Composer Session List** (window intersects daily period).
6. **UsageEventAttributor** → map each daily **Usage Event** to at most one `composerId` (or unassigned).
7. **UsageSummaryBuilder** → global summary from global events; **Selected Session Summary** from events grouped by selected `composerId`.
8. Controller passes view data: global summary, session list, selected session summary (nullable), counts, invalid-composer redirect handled in request layer or controller.

### Deep modules (interfaces and seams)

#### 1. `ComposerSessionRegistry` (new interface)

**Purpose:** Read **Composer Session Registry** from Cursor `state.vscdb`; hide JSON shape and SQLite access.

**Interface (caller knows):**

- `listAll(): list<ComposerSessionDto>` — raw registry entries (or filtered only by readable DB).
- DTO fields: `composerId`, `name` (**Composer Session Title**), `createdAtMs`, `lastUpdatedAtMs|null`, `workspacePath`, `workspaceHash`, `unifiedMode` (`agent`|`chat`).

**Adapter:** reads `composer.composerHeaders` → `allComposers[]`; reuses same database path resolution as existing SQLite auth adapter (config `CURSOR_STATS_SQLITE_PATH`).

**Depth / locality:** JSON nesting, null `lastUpdatedAt`, `workspaceIdentifier` shape, and platform path defaults live here — not in the controller or attributor.

**Deletion test:** Without this module, parsing leaks into three callers.

#### 2. `DailyComposerSessionListBuilder` (new, optional thin module)

**Purpose:** Filter and sort registry entries for the **Daily Composer Session List**.

**Interface:**

- `build(list<ComposerSessionDto> $sessions, ReportingPeriod $dailyPeriod, CarbonImmutable $now): list<ComposerSessionDto>`
- Inclusion: `[createdAt, lastUpdatedAt ?? now]` intersects `[dailyPeriod.startMs, dailyPeriod.endMs]`.
- Sort: `lastUpdatedAt` descending; nulls last (fallback sort key `createdAt` documented in tests).

**Depth:** Calendar intersection in **Reporting Timezone** is easy to get wrong; keeps registry adapter dumb.

*Alternative:* fold into registry or controller if deemed shallow — prefer separate module if intersection logic exceeds ~15 lines.

#### 3. `UsageEventAttributor` (new interface)

**Purpose:** **Estimated Attribution** for one day’s events.

**Interface:**

- Input: `list<UsageEventDto> $events`, `list<ComposerSessionDto> $sessions`, `ReportingPeriod $dailyPeriod`, `CarbonImmutable $now`.
- Output: value object e.g. `AttributionResult` with `map<composerId, list<UsageEventDto>>` and `list<UsageEventDto> $unassigned`.

**Rules:**

- Candidate sessions: `createdAt <= event.timestamp <= (lastUpdatedAt ?? end of daily period ?? now)`.
- **Attribution Tie-Break:** among candidates, pick session with greatest `createdAt` still `<= event.timestamp` (**dernière session ouverte**).
- `isHeadless` events: same rules (no special bucket).
- One event → at most one session.

**Depth:** Tie-break and overlap logic are the fragile domain; must not live in Blade or controller.

#### 4. `UsageSummaryBuilder` (extend existing module)

**Purpose:** Unchanged aggregation rules; extend output for session UI.

**Interface change:**

- Add `tokenBasedEventCount` to **UsageSummary** (or separate small value object returned alongside).
- Global dashboard keeps displaying `eventCount` (all types) under **Montant réel**.
- **Selected Session Summary** displays **Token-Based Event Count** under **Montant réel**.

**Depth:** Already deep; extend interface minimally — do not fork aggregation logic.

#### 5. `SessionBreakdownPresenter` or controller orchestration (thin)

**Purpose:** Compose attribution + summaries + counts for the view.

**May compute:**

- `unattributedEventCount` = count of unassigned daily events (all types).
- Selected session events from attribution map.
- Redirect when `composer` query not in daily list (strip param, 302 to same path without it).

**Keep shallow:** no SQLite, no tie-break math — only wiring.

#### 6. `UsageDashboardRequest` (extend)

**Validation:**

- Optional `composer` UUID query param.
- After daily list built: if `composer` present but not in list → trigger redirect response (invalid id).

**Period links:** presets should retain valid `composer` when still in daily list; invalid composer stripped on redirect.

#### 7. `CursorUsageClient` (unchanged seam)

No composer logic in HTTP adapter. Optional future: accept two periods in one controller call = two `fetchUsageEvents` invocations.

#### 8. View layer

- Split dashboard content into two `<section>` columns (`lg:grid-cols-2`).
- Left: existing **Usage Summary** markup (unchanged semantics).
- Right: session list (links with `?composer=`), empty state copy when none selected, duplicated summary cards component or shared partial for **Selected Session Summary**.
- French copy; no disclaimer banner (personal use).

### Configuration

- Reuse `CURSOR_STATS_SQLITE_PATH` for **Composer Session Registry** (same file as **Session Credential**).
- Optional `CURSOR_STATS_ATTRIBUTION_ENABLED` default `true` — if false, hide right column (nice-to-have; omit if YAGNI).

### Explicit non-sources

- **Agent Transcript**, **Workspace Generation**, `store.db`: not used (no per-message timestamps, no `composerId` on generations, no billing fields on API).
- Attribution is API events → session windows, not « events in session matched to API ».

---

## Testing Decisions

### What makes a good test here

- Test **module interfaces** (attribution map, daily list filter, summary counts) — not Blade HTML or private methods.
- Use fixed millisecond timestamps and in-memory DTOs; no live Cursor SQLite or `cursor.com` in CI.
- Prior art: `tests/Unit/Cursor/UsageSummaryBuilderTest.php`, `tests/Feature/Cursor/HttpCursorUsageClientTest.php` (HTTP faked), resolver tests with temp SQLite if present.

### Modules to test (recommended)

| Module | Unit tests | Focus |
|--------|------------|--------|
| **UsageEventAttributor** | Yes | Overlap + **Attribution Tie-Break**; unassigned; `lastUpdatedAt` null; boundary ms |
| **DailyComposerSessionListBuilder** (if extracted) | Yes | Intersection with daily period; sort order |
| **ComposerSessionRegistry** | Yes | Fixture `state.vscdb` with truncated `composer.composerHeaders` JSON |
| **UsageSummaryBuilder** | Yes (extend) | `tokenBasedEventCount` exposed correctly |
| **UsageDashboardController** | Feature | Invalid `composer` redirect; selected session summary present when param valid; global unchanged |
| **CursorUsageClient** | Existing only | No new live API tests |

### Not testing in MVP

- Real user `~/Library/.../state.vscdb` in CI.
- Visual regression of two-column layout.
- Exact parity between global event total and sum of per-thread + unassigned (not guaranteed by product).

---

## Out of Scope

- **Date Range** on session list (multi-day thread history in selector).
- Per-event list, drill-down, charts.
- **Agent Transcript**, `aiService.generations`, `store.db` parsing.
- Workspace filter toggle (all projects in daily list for MVP).
- Disclaimer / « attribution estimée » banner.
- Dedicated route `/sessions` (same page only).
- Database storage, queues, auto-refresh.
- Guaranteed 100% attribution accuracy.
- `isHeadless` separate bucket.
- « Non attribué » as selectable list row.
- **Unattributed Event Count** visible without a selected thread.
- Enterprise Admin API.
- Exposing `tokenBasedEventCount` on global **Usage Summary** (session column only).

---

## Further Notes

- **Performance:** Page reload may trigger two paginated API fetches when session column enabled (global range + daily). Acceptable for personal tool; optimize later with caching only if needed.
- **PRD publication:** This file lives in `docs/prd/`; implementation tracking via GitHub issues on `geoffroyriou-weqeep/cursor-stats`.
- **Issue split:**
  - **#7 — Mécanique** : registry, attribution, builder, controller, request, tests ; Blade minimal (données + liens `?composer=`, pas de layout deux colonnes ni polish Tailwind).
  - **#8 — UI** (bloquée par #7) : grille 50/50, partials cartes, liste fils stylée, états vides, responsive, cohérence visuelle avec le dashboard existant.
- **Implementation order:** #7 puis #8.
