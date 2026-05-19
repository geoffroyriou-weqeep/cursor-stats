<?php

namespace App\Services\Cursor;

use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;

final class EnvSessionCredentialResolver implements SessionCredentialResolver
{
    public function resolve(): SessionCredential
    {
        $cookie = config('cursor_stats.session_cookie');

        if (! is_string($cookie) || trim($cookie) === '') {
            throw new CursorSessionUnavailableException(
                'Set CURSOR_SESSION_COOKIE in your .env file (WorkosCursorSessionToken value from cursor.com).',
            );
        }

        return SessionCredential::fromTokenValue($cookie);
    }
}
