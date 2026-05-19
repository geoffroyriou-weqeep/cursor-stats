<?php

return [

    'timezone' => env('CURSOR_STATS_TIMEZONE', 'Europe/Paris'),

    'session_cookie' => env('CURSOR_SESSION_COOKIE'),

    'sqlite_path' => env('CURSOR_STATS_SQLITE_PATH'),

    'api_base_url' => env('CURSOR_STATS_API_BASE_URL', 'https://cursor.com'),

    'page_size' => (int) env('CURSOR_STATS_PAGE_SIZE', 100),

];
