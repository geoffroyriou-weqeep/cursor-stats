@extends('layouts.app')

@section('title', 'Cursor Stats — '.$period->label)

@section('content')
    <header class="mb-8">
        <p class="text-sm font-medium text-zinc-500">Cursor Stats</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $period->label }}</h1>
        <p class="mt-1 text-sm text-zinc-500">
            Fuseau : {{ config('cursor_stats.timezone') }} — rechargez la page pour actualiser.
        </p>

        <nav class="mt-4 flex flex-wrap gap-2" aria-label="Période">
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
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        'bg-zinc-900 text-white' => $isActive,
                        'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50' => ! $isActive,
                    ])
                    @if ($isActive) aria-current="page" @endif
                >
                    {{ $presetOption->label() }}
                </a>
            @endforeach
        </nav>

        <form
            method="GET"
            action="{{ url('/') }}"
            class="mt-4 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm"
            aria-label="Période personnalisée"
        >
            <p class="text-sm font-medium text-zinc-700">Personnalisé</p>
            <div class="mt-3 flex flex-wrap items-end gap-3">
                <div>
                    <label for="from" class="block text-xs font-medium text-zinc-500">Du</label>
                    <input
                        id="from"
                        type="date"
                        name="from"
                        value="{{ old('from', $customFrom) }}"
                        required
                        class="mt-1 rounded-lg border border-zinc-200 px-2 py-1.5 text-sm text-zinc-900"
                    />
                </div>
                <div>
                    <label for="to" class="block text-xs font-medium text-zinc-500">Au</label>
                    <input
                        id="to"
                        type="date"
                        name="to"
                        value="{{ old('to', $customTo) }}"
                        required
                        class="mt-1 rounded-lg border border-zinc-200 px-2 py-1.5 text-sm text-zinc-900"
                    />
                </div>
                <button
                    type="submit"
                    @class([
                        'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                        'bg-zinc-900 text-white' => $isCustomRange,
                        'bg-white text-zinc-700 ring-1 ring-zinc-200 hover:bg-zinc-50' => ! $isCustomRange,
                    ])
                    @if ($isCustomRange) aria-current="page" @endif
                >
                    Appliquer
                </button>
            </div>
            @if ($errors->any())
                <p class="mt-3 text-sm text-red-600">{{ $errors->first() }}</p>
            @endif
        </form>
    </header>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 bg-white shadow-sm">
        <div class="flex items-baseline justify-between gap-4 px-5 py-4">
            <dt class="text-sm font-medium text-zinc-600">Input</dt>
            <dd class="text-lg font-semibold tabular-nums">{{ $summary->formattedTokens($summary->inputTokens) }}</dd>
        </div>
        <div class="flex items-baseline justify-between gap-4 px-5 py-4">
            <dt class="text-sm font-medium text-zinc-600">Output</dt>
            <dd class="text-lg font-semibold tabular-nums">{{ $summary->formattedTokens($summary->outputTokens) }}</dd>
        </div>
        <div class="flex items-baseline justify-between gap-4 px-5 py-4">
            <dt class="text-sm font-medium text-zinc-600">Cache read</dt>
            <dd class="text-lg font-semibold tabular-nums">{{ $summary->formattedTokens($summary->cacheReadTokens) }}</dd>
        </div>
    </dl>

    <div class="mt-6 rounded-xl border border-zinc-200 bg-white px-5 py-5 shadow-sm">
        <p class="text-sm font-medium text-zinc-600">Montant réel</p>
        <p class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">{{ $summary->formattedCost() }}</p>
        <p class="mt-2 text-xs text-zinc-500">
            {{ $summary->eventCount }} événement(s) sur la période — coût agrégé des appels token-based uniquement.
        </p>
    </div>
@endsection
