<!DOCTYPE html>
<html lang="fr" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Cursor Stats')</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full antialiased">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-32 top-0 h-[28rem] w-[28rem] rounded-full bg-violet-600/20 blur-[100px]"></div>
            <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-cyan-500/15 blur-[90px]"></div>
            <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-fuchsia-600/10 blur-[80px]"></div>
        </div>

        <main class="relative mx-auto min-h-screen max-w-3xl px-5 py-10 sm:px-8 sm:py-14">
            @yield('content')
        </main>
    </body>
</html>
