<?php

use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\Resolvers\SqliteSessionCredentialResolver;

function createCursorStateDatabase(string $token): string
{
    $dbPath = sys_get_temp_dir().'/cursor-stats-'.uniqid().'.vscdb';
    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->exec('CREATE TABLE ItemTable (key TEXT UNIQUE ON CONFLICT REPLACE, value BLOB)');
    $statement = $pdo->prepare('INSERT INTO ItemTable (key, value) VALUES (?, ?)');
    $statement->execute(['cursorAuth/accessToken', $token]);

    return $dbPath;
}

it('reads access token from sqlite database', function () {
    $jwt = testCursorAccessTokenJwt('google-oauth2|user_01SQLITE');
    $dbPath = createCursorStateDatabase($jwt);
    config(['cursor_stats.sqlite_path' => $dbPath]);

    $credential = (new SqliteSessionCredentialResolver)->resolve();

    expect($credential->cookieHeader)->toBe('WorkosCursorSessionToken=user_01SQLITE::'.$jwt);

    unlink($dbPath);
});

it('uses configured sqlite path override', function () {
    $dbPath = createCursorStateDatabase('override-token');
    config(['cursor_stats.sqlite_path' => $dbPath]);

    expect(SqliteSessionCredentialResolver::databasePath())->toBe($dbPath);

    unlink($dbPath);
});

it('throws when database file is missing', function () {
    config([
        'cursor_stats.sqlite_path' => sys_get_temp_dir().'/cursor-stats-missing-'.uniqid().'.vscdb',
    ]);

    (new SqliteSessionCredentialResolver)->resolve();
})->throws(CursorSessionUnavailableException::class);

it('throws when access token row is empty', function () {
    $dbPath = sys_get_temp_dir().'/cursor-stats-empty-'.uniqid().'.vscdb';
    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->exec('CREATE TABLE ItemTable (key TEXT UNIQUE ON CONFLICT REPLACE, value BLOB)');

    config(['cursor_stats.sqlite_path' => $dbPath]);

    (new SqliteSessionCredentialResolver)->resolve();
})->throws(CursorSessionUnavailableException::class);
