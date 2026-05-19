@extends('layouts.app')

@section('title', 'Cursor Stats — session requise')

@section('content')
    <header class="mb-8">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 shadow-lg shadow-red-500/30">
                <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-red-400/90">Session Cursor indisponible</p>
                <h1 class="mt-0.5 text-2xl font-bold tracking-tight text-white">Authentification requise</h1>
            </div>
        </div>
    </header>

    <div class="glass-raised overflow-hidden rounded-2xl border-red-500/20">
        <div class="border-b border-red-500/10 bg-red-500/5 px-5 py-4">
            <p class="text-sm leading-relaxed text-red-200/90">{{ $message }}</p>
        </div>

        <div class="px-5 py-5">
            <h2 class="text-sm font-semibold text-zinc-200">Étapes recommandées</h2>
            <ol class="mt-4 space-y-4">
                <li class="flex gap-3 text-sm text-zinc-400">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-zinc-300">1</span>
                    <span>
                        Ouvrez <strong class="text-zinc-200">Cursor</strong> sur cette machine et vérifiez que vous êtes connecté
                        (menu compte / connexion active).
                    </span>
                </li>
                <li class="flex gap-3 text-sm text-zinc-400">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-zinc-300">2</span>
                    <span>
                        Rechargez cette page : le token est lu automatiquement depuis la base SQLite locale&nbsp;:
                        <code class="mt-2 block break-all rounded-lg border border-white/10 bg-black/30 px-3 py-2 font-mono text-xs text-zinc-300">{{ $sqlitePath }}</code>
                        Pour un emplacement personnalisé, définissez
                        <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-xs text-violet-300">CURSOR_STATS_SQLITE_PATH</code>
                        dans <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-xs text-violet-300">.env</code>.
                    </span>
                </li>
                <li class="flex gap-3 text-sm text-zinc-400">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-bold text-zinc-300">3</span>
                    <span>
                        Si la lecture SQLite échoue, copiez le cookie
                        <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-xs text-violet-300">WorkosCursorSessionToken</code>
                        depuis les outils développeur sur
                        <a class="font-medium text-violet-400 underline decoration-violet-400/30 underline-offset-2 hover:text-violet-300" href="https://cursor.com/dashboard?tab=usage" target="_blank" rel="noopener">cursor.com</a>
                        et ajoutez-le dans <code class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-xs text-violet-300">.env</code>&nbsp;:
                        <code class="mt-2 block rounded-lg border border-white/10 bg-black/30 px-3 py-2 font-mono text-xs text-zinc-300">CURSOR_SESSION_COOKIE=…</code>
                    </span>
                </li>
            </ol>
        </div>
    </div>

    <p class="mt-8 text-center">
        <a href="{{ url('/') }}" class="text-sm font-medium text-violet-400 hover:text-violet-300">← Réessayer</a>
    </p>
@endsection
