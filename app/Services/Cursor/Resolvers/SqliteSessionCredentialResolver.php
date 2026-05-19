<?php

namespace App\Services\Cursor\Resolvers;

use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\Dto\SessionCredential;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use PDO;

final class SqliteSessionCredentialResolver implements SessionCredentialResolver
{
    private const ACCESS_TOKEN_KEY = 'cursorAuth/accessToken';

    public function resolve(): SessionCredential
    {
        $path = self::databasePath();

        if (! is_readable($path)) {
            throw new CursorSessionUnavailableException(
                'Cursor local database not found or not readable at: '.$path,
            );
        }

        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $statement = $pdo->prepare('SELECT value FROM ItemTable WHERE key = ?');
        $statement->execute([self::ACCESS_TOKEN_KEY]);
        $token = $statement->fetchColumn();

        if (! is_string($token) || trim($token) === '') {
            throw new CursorSessionUnavailableException(
                'No access token in Cursor database. Open Cursor and sign in, then reload this page.',
            );
        }

        return SessionCredential::fromAccessToken($token);
    }

    public static function databasePath(): string
    {
        $configured = config('cursor_stats.sqlite_path');

        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        $home = $_SERVER['HOME'] ?? getenv('HOME') ?: '';

        return $home.'/Library/Application Support/Cursor/User/globalStorage/state.vscdb';
    }
}
