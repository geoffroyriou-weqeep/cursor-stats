@props(['summary', 'showTokenBasedCount' => false])

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Input --}}
        <div
            class="group relative overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 transition hover:border-sky-300">
            <div
                class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-sky-200/60 blur-2xl transition group-hover:bg-sky-300/50">
            </div>
            <dt class="flex items-center gap-2 text-sm font-medium text-sky-700">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-100">
                    <svg class="h-4 w-4 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </span>
                Input
            </dt>
            <dd class="relative mt-3 text-2xl font-bold tabular-nums tracking-tight text-zinc-900">
                {{ $summary->formattedTokens($summary->inputTokens) }}
            </dd>
        </div>

        {{-- Output --}}
        <div
            class="group relative overflow-hidden rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 transition hover:border-violet-300">
            <div
                class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-violet-200/60 blur-2xl transition group-hover:bg-violet-300/50">
            </div>
            <dt class="flex items-center gap-2 text-sm font-medium text-violet-700">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100">
                    <svg class="h-4 w-4 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </span>
                Output
            </dt>
            <dd class="relative mt-3 text-2xl font-bold tabular-nums tracking-tight text-zinc-900">
                {{ $summary->formattedTokens($summary->outputTokens) }}
            </dd>
        </div>

        {{-- Cache read --}}
        <div
            class="group relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 transition hover:border-emerald-300">
            <div
                class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-200/60 blur-2xl transition group-hover:bg-emerald-300/50">
            </div>
            <dt class="flex items-center gap-2 text-sm font-medium text-emerald-700">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                    <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                    </svg>
                </span>
                Cache read
            </dt>
            <dd class="relative mt-3 text-2xl font-bold tabular-nums tracking-tight text-zinc-900">
                {{ $summary->formattedTokens($summary->cacheReadTokens) }}
            </dd>
        </div>
    </div>

    {{-- Contexte moyen --}}
    <div
        class="group relative overflow-hidden rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white p-5 transition hover:border-rose-300 sm:p-6">
        <div
            class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-rose-200/60 blur-2xl transition group-hover:bg-rose-300/50">
        </div>
        <dt class="flex items-center gap-2 text-sm font-medium text-rose-700">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-100">
                <svg class="h-4 w-4 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </span>
            Contexte moyen
        </dt>
        <dd class="relative mt-3 text-2xl font-bold tabular-nums tracking-tight text-zinc-900">
            {{ $summary->formattedTokens($summary->averageContextSize) }}
        </dd>
        <p class="relative mt-2 text-xs leading-relaxed text-rose-700/70 sm:mt-0">
            Moyenne des tokens envoyés au modèle, par appel.
        </p>
    </div>
</div>

{{-- Montant réel --}}
<div
    class="glow-amber relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 via-amber-50/80 to-white p-6 sm:p-7">
    <div
        class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-amber-200/40 via-transparent to-transparent">
    </div>
    <div class="relative">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <p class="text-sm font-medium text-amber-800">Montant réel</p>
            <span class="rounded-full border border-amber-200 bg-white px-2.5 py-0.5 text-xs text-amber-700">
                @if ($showTokenBasedCount)
                    {{ $summary->tokenBasedEventCount }}
                @else
                    {{ $summary->eventCount }} 
                @endif
                événement{{ $summary->eventCount > 1 ? 's' : '' }}
            </span>
        </div>
        <p class="mt-2 text-4xl font-bold tabular-nums tracking-tight text-amber-900 sm:text-5xl">
            {{ $summary->formattedCost() }}
        </p>
        <p class="mt-4 max-w-prose text-sm leading-relaxed text-amber-800/70">
            @if ($showTokenBasedCount)
                Coût agrégé des appels token-based attribués à ce fil.
            @else
                Coût agrégé des appels token-based uniquement.
            @endif
        </p>
    </div>
</div>
