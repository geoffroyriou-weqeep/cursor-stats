@extends('layouts.app')

@section('title', 'Cursor Stats — session requise')

@section('content')
    <header class="mb-6">
        <p class="text-sm font-medium text-red-600">Session Cursor indisponible</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Authentification requise</h1>
    </header>

    <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-5 text-sm text-red-900">
        <p>{{ $message }}</p>

        <h2 class="mt-5 font-semibold">Étapes recommandées</h2>
        <ol class="mt-3 list-decimal space-y-3 pl-5">
            <li>
                Ouvrez <strong>Cursor</strong> sur cette machine et vérifiez que vous êtes connecté
                (menu compte / connexion active).
            </li>
            <li>
                Rechargez cette page : le token est lu automatiquement depuis la base SQLite locale&nbsp;:
                <code class="mt-1 block break-all rounded bg-red-100 px-2 py-1 text-xs">{{ $sqlitePath }}</code>
                Pour un emplacement personnalisé, définissez
                <code class="rounded bg-red-100 px-1">CURSOR_STATS_SQLITE_PATH</code> dans
                <code class="rounded bg-red-100 px-1">.env</code>.
            </li>
            <li>
                Si la lecture SQLite échoue, copiez le cookie
                <code class="rounded bg-red-100 px-1">WorkosCursorSessionToken</code> depuis les outils
                développeur sur
                <a class="underline" href="https://cursor.com/dashboard?tab=usage" target="_blank" rel="noopener">cursor.com</a>
                et ajoutez-le dans <code class="rounded bg-red-100 px-1">.env</code>&nbsp;:
                <code class="mt-1 block rounded bg-red-100 px-2 py-1 text-xs">CURSOR_SESSION_COOKIE=…</code>
            </li>
        </ol>
    </div>
@endsection
