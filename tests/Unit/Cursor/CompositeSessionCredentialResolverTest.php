<?php

use App\Services\Cursor\CompositeSessionCredentialResolver;
use App\Services\Cursor\EnvSessionCredentialResolver;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\SqliteSessionCredentialResolver;

it('falls back to env cookie when sqlite has no token', function () {
    $missingDb = sys_get_temp_dir().'/cursor-stats-missing-'.uniqid().'.vscdb';

    config([
        'cursor_stats.sqlite_path' => $missingDb,
        'cursor_stats.session_cookie' => 'env-fallback-token',
    ]);

    $resolver = new CompositeSessionCredentialResolver([
        new SqliteSessionCredentialResolver,
        new EnvSessionCredentialResolver,
    ]);

    $credential = $resolver->resolve();

    expect($credential->cookieHeader)->toBe('WorkosCursorSessionToken=env-fallback-token');
});

it('prefers sqlite token over env cookie', function () {
    $jwt = testCursorAccessTokenJwt('google-oauth2|user_01SQLITE');
    $dbPath = sys_get_temp_dir().'/cursor-stats-'.uniqid().'.vscdb';
    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->exec('CREATE TABLE ItemTable (key TEXT UNIQUE ON CONFLICT REPLACE, value BLOB)');
    $statement = $pdo->prepare('INSERT INTO ItemTable (key, value) VALUES (?, ?)');
    $statement->execute(['cursorAuth/accessToken', $jwt]);

    config([
        'cursor_stats.sqlite_path' => $dbPath,
        'cursor_stats.session_cookie' => 'env-fallback-token',
    ]);

    $resolver = new CompositeSessionCredentialResolver([
        new SqliteSessionCredentialResolver,
        new EnvSessionCredentialResolver,
    ]);

    $credential = $resolver->resolve();

    expect($credential->cookieHeader)->toBe('WorkosCursorSessionToken=user_01SQLITE::'.$jwt);

    unlink($dbPath);
});

it('throws when all resolvers fail', function () {
    config([
        'cursor_stats.sqlite_path' => sys_get_temp_dir().'/cursor-stats-missing-'.uniqid().'.vscdb',
        'cursor_stats.session_cookie' => null,
    ]);

    $resolver = new CompositeSessionCredentialResolver([
        new SqliteSessionCredentialResolver,
        new EnvSessionCredentialResolver,
    ]);

    $resolver->resolve();
})->throws(CursorSessionUnavailableException::class);
