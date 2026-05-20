@extends('layouts.app')

@section('title', 'Cursor Stats — ' . $period->label)

@section('content')
    @php
        $composerQuery = $selectedComposerId ? ['composer' => $selectedComposerId] : [];
    @endphp

    {{-- En-tête --}}
    <header class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-600 shadow-lg shadow-violet-500/30">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-8 4 5 5-9" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-violet-600">Cursor Stats</p>
                    <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $period->label }}</h1>
                </div>
            </div>
        </div>
        <p class="mt-3 text-sm text-zinc-500 sm:hidden">{{ config('cursor_stats.timezone') }} · rechargez pour actualiser
        </p>
        <p class="mt-3 hidden text-sm text-zinc-500 sm:block">Rechargez la page pour actualiser les données.</p>
    </header>

    <div class="grid min-w-0 grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-6">
        {{-- Colonne gauche (50 %) : période + résumé par Date Range --}}
        <section aria-label="Usage Summary" class="min-w-0 space-y-5 rounded-3xl bg-white p-6 ring-1 ring-white/10 border border-zinc-200">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-zinc-500">Par période</h2>

            <div class="glass-raised overflow-hidden rounded-2xl">
                <div class="border-b border-zinc-100 px-4 py-3 sm:px-5">
                    <p class="text-xs font-medium text-zinc-500">Période</p>
                    <nav class="mt-3 flex flex-wrap gap-1.5 p-1" aria-label="Période">
                        @foreach (\App\Services\Cursor\Enums\DatePreset::cases() as $presetOption)
                            @php
                                $href =
                                    $presetOption === \App\Services\Cursor\Enums\DatePreset::Today
                                        ? $dashboardRequest->urlWithQuery($composerQuery)
                                        : $dashboardRequest->urlWithQuery(
                                            array_merge(['preset' => $presetOption->value], $composerQuery),
                                        );
                                $isActive = !$isCustomRange && $preset === $presetOption;
                            @endphp
                            <a href="{{ $href }}" @class([
                                'rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200',
                                'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-md shadow-violet-500/25' => $isActive,
                                'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800' => !$isActive,
                            ])
                                @if ($isActive) aria-current="page" @endif>
                                {{ $presetOption->label() }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                <form method="GET" action="{{ url('/') }}" class="px-4 py-4 sm:px-5" aria-label="Période personnalisée">
                    <p class="text-xs font-medium text-zinc-500">Plage personnalisée</p>
                    <div class="mt-3 flex flex-wrap items-end gap-3">
                        <div class="min-w-[8.5rem] flex-1">
                            <label for="from" class="mb-1.5 block text-xs font-medium text-zinc-500">Du</label>
                            <input id="from" type="date" name="from" value="{{ old('from', $customFrom) }}" required
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-500/15 [color-scheme:light]" />
                        </div>
                        <div class="min-w-[8.5rem] flex-1">
                            <label for="to" class="mb-1.5 block text-xs font-medium text-zinc-500">Au</label>
                            <input id="to" type="date" name="to" value="{{ old('to', $customTo) }}" required
                                class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-500/15 [color-scheme:light]" />
                        </div>
                        @if ($selectedComposerId)
                            <input type="hidden" name="composer" value="{{ $selectedComposerId }}" />
                        @endif
                        <button type="submit" @class([
                            'rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200',
                            'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40' => $isCustomRange,
                            'border border-zinc-200 bg-zinc-50 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => !$isCustomRange,
                        ])
                            @if ($isCustomRange) aria-current="page" @endif>
                            Appliquer
                        </button>
                    </div>
                    @if ($errors->any())
                        <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ $errors->first() }}</p>
                    @endif
                </form>
            </div>

            <p class="text-xs text-zinc-500">{{ $period->label }}</p>
            @include('usage.partials.summary-cards', ['summary' => $summary, 'showTokenBasedCount' => false])
        </section>

        {{-- Colonne droite (50 %) : résumé par Composer Session — thème sombre --}}
        <section aria-label="Session Usage Breakdown"
            class="dark min-w-0 space-y-5 rounded-3xl bg-slate-700 p-6 ring-1 ring-white/10">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-widest text-zinc-400">Par fil</h2>
            </div>

            <div
                class="h-[223px] overflow-hidden rounded-2xl border border-zinc-700/50 bg-slate-800 px-4 py-4 backdrop-blur-xl shadow-[0_4px_24px_rgb(0_0_0/0.25)] sm:px-5">
                <label for="composer" class="mb-1.5 block text-xs font-medium text-zinc-200">Fil Composer</label>
                @if ($dailySessions === [])
                    <p class="text-sm text-zinc-400">Aucun fil actif aujourd'hui.</p>
                @else
                    <select id="composer" name="composer" aria-label="Choisir un fil Composer"
                        class="w-full rounded-xl border border-slate-600 bg-slate-700/60 px-3 py-2.5 text-sm text-zinc-100 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-500/25 [color-scheme:dark]"
                        onchange="window.location.assign(this.value || @js($dashboardRequest->urlWithoutComposer()))">
                        <option value="" @selected($selectedComposerId === null)>Choisir un fil…</option>
                        @foreach ($dailySessions as $session)
                            @php
                                $sessionHref = $dashboardRequest->urlWithQuery(['composer' => $session->composerId]);
                                $optionLabel = $session->displayTitle();
                                if ($session->workspacePath) {
                                    $optionLabel .= ' — ' . \Illuminate\Support\Str::limit($session->workspacePath, 48);
                                }
                            @endphp
                            <option value="{{ $sessionHref }}" @selected($selectedComposerId === $session->composerId)>
                                {{ $optionLabel }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            @if ($selectedSession === null)
                <div
                    class="rounded-2xl border border-dashed border-zinc-700 bg-zinc-800/40 px-4 py-10 text-center sm:px-5">
                    <p class="text-sm font-medium text-zinc-300">Choisir un fil</p>
                    <p class="mt-2 text-xs text-zinc-500">Sélectionnez un fil dans la liste déroulante pour voir son
                        résumé du jour.</p>
                </div>
            @else
                <div class="space-y-5">
                    @if ($selectedSession->workspacePath)
                        <p class="truncate text-xs text-zinc-400" title="{{ $selectedSession->workspacePath }}">
                            {{ \Illuminate\Support\Str::limit($selectedSession->workspacePath, 72) }}
                        </p>
                    @endif

                    @include('usage.partials.summary-cards', [
                        'summary' => $selectedSummary,
                        'showTokenBasedCount' => true,
                    ])

                    @if ($unattributedEventCount > 0)
                        <p class="text-xs text-zinc-500">
                            {{ $unattributedEventCount }} appel{{ $unattributedEventCount > 1 ? 's' : '' }} non
                            rattaché{{ $unattributedEventCount > 1 ? 's' : '' }} à un fil aujourd'hui.
                        </p>
                    @endif
                </div>
            @endif
        </section>
    </div>
@endsection
