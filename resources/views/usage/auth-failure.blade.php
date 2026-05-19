@extends('layouts.app')

@section('title', 'Cursor Stats — session requise')

@section('content')
    <header class="mb-6">
        <p class="text-sm font-medium text-red-600">Session Cursor indisponible</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Authentification requise</h1>
    </header>

    <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-5 text-sm text-red-900">
        <p>{{ $message }}</p>
        <ul class="mt-4 list-disc space-y-2 pl-5">
            <li>Connectez-vous sur <a class="underline" href="https://cursor.com/dashboard?tab=usage" target="_blank" rel="noopener">cursor.com</a>.</li>
            <li>Copiez la valeur du cookie <code class="rounded bg-red-100 px-1">WorkosCursorSessionToken</code> depuis les outils développeur.</li>
            <li>Ajoutez-la dans <code class="rounded bg-red-100 px-1">.env</code> : <code class="rounded bg-red-100 px-1">CURSOR_SESSION_COOKIE=…</code></li>
        </ul>
    </div>
@endsection
