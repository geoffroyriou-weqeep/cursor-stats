@extends('layouts.app')

@section('title', 'Cursor Stats — '.$period->label)

@section('content')
    {{-- En-tête --}}
    <header class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-600 shadow-lg shadow-violet-500/30">
                    <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-8 4 5 5-9" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-violet-600">Cursor Stats</p>
                    <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl">{{ $period->label }}</h1>
                </div>
            </div>
        </div>
        <p class="mt-3 text-sm text-zinc-500 sm:hidden">{{ config('cursor_stats.timezone') }} · rechargez pour actualiser</p>
        <p class="mt-3 hidden text-sm text-zinc-500 sm:block">Rechargez la page pour actualiser les données.</p>
    </header>

    {{-- Sélecteur de période --}}
    <div class="glass-raised mb-8 overflow-hidden rounded-2xl">
        <div class="border-b border-zinc-100 px-4 py-3 sm:px-5">
            <p class="text-xs font-medium text-zinc-500">Période</p>
            <nav class="mt-3 flex flex-wrap gap-1.5 p-1" aria-label="Période">
                @foreach (\App\Services\Cursor\DatePreset::cases() as $presetOption)
                    @php
                        $href = $presetOption === \App\Services\Cursor\DatePreset::Today
                            ? url('/')
                            : url('/?preset='.$presetOption->value);
                        $isActive = ! $isCustomRange && $preset === $presetOption;
                    @endphp
                    <a
                        href="{{ $href }}"
                        @class([
                            'rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200',
                            'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-md shadow-violet-500/25' => $isActive,
                            'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800' => ! $isActive,
                        ])
                        @if ($isActive) aria-current="page" @endif
                    >
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
                    <input
                        id="from"
                        type="date"
                        name="from"
                        value="{{ old('from', $customFrom) }}"
                        required
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-500/15 [color-scheme:light]"
                    />
                </div>
                <div class="min-w-[8.5rem] flex-1">
                    <label for="to" class="mb-1.5 block text-xs font-medium text-zinc-500">Au</label>
                    <input
                        id="to"
                        type="date"
                        name="to"
                        value="{{ old('to', $customTo) }}"
                        required
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2.5 text-sm text-zinc-900 outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-500/15 [color-scheme:light]"
                    />
                </div>
                <button
                    type="submit"
                    @class([
                        'rounded-xl px-5 py-2.5 text-sm font-semibold transition-all duration-200',
                        'bg-gradient-to-r from-violet-500 to-fuchsia-500 text-white shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40' => $isCustomRange,
                        'border border-zinc-200 bg-zinc-50 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => ! $isCustomRange,
                    ])
                    @if ($isCustomRange) aria-current="page" @endif
                >
                    Appliquer
                </button>
            </div>
            @if ($errors->any())
                <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600">{{ $errors->first() }}</p>
            @endif
        </form>
    </div>

    {{-- Stats tokens --}}
    <section aria-label="Usage Summary" class="space-y-5">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- Input --}}
            <div class="group relative overflow-hidden rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 transition hover:border-sky-300">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-sky-200/60 blur-2xl transition group-hover:bg-sky-300/50"></div>
                <dt class="flex items-center gap-2 text-sm font-medium text-sky-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-100">
                        <svg class="h-4 w-4 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </span>
                    Input
                </dt>
                <dd class="relative mt-3 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                    {{ $summary->formattedTokens($summary->inputTokens) }}
                </dd>
            </div>

            {{-- Output --}}
            <div class="group relative overflow-hidden rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-white p-5 transition hover:border-violet-300">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-violet-200/60 blur-2xl transition group-hover:bg-violet-300/50"></div>
                <dt class="flex items-center gap-2 text-sm font-medium text-violet-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-100">
                        <svg class="h-4 w-4 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </span>
                    Output
                </dt>
                <dd class="relative mt-3 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                    {{ $summary->formattedTokens($summary->outputTokens) }}
                </dd>
            </div>

            {{-- Cache read --}}
            <div class="group relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-white p-5 transition hover:border-emerald-300">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-200/60 blur-2xl transition group-hover:bg-emerald-300/50"></div>
                <dt class="flex items-center gap-2 text-sm font-medium text-emerald-700">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                        <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                        </svg>
                    </span>
                    Cache read
                </dt>
                <dd class="relative mt-3 text-3xl font-bold tabular-nums tracking-tight text-zinc-900">
                    {{ $summary->formattedTokens($summary->cacheReadTokens) }}
                </dd>
            </div>
        </dl>

        {{-- Montant réel --}}
        <div class="glow-amber relative overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 via-amber-50/80 to-white p-6 sm:p-7">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-amber-200/40 via-transparent to-transparent"></div>
            <div class="relative">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <p class="text-sm font-medium text-amber-800">Montant réel</p>
                    <span class="rounded-full border border-amber-200 bg-white px-2.5 py-0.5 text-xs text-amber-700">
                        {{ $summary->eventCount }} événement{{ $summary->eventCount > 1 ? 's' : '' }}
                    </span>
                </div>
                <p class="mt-2 text-4xl font-bold tabular-nums tracking-tight text-amber-900 sm:text-5xl">
                    {{ $summary->formattedCost() }}
                </p>
                <p class="mt-4 max-w-prose text-sm leading-relaxed text-amber-800/70">
                    Coût agrégé des appels token-based uniquement.
                </p>
            </div>
        </div>
    </section>
@endsection
