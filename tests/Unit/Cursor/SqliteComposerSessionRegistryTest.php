<?php

use App\Services\Cursor\Registries\SqliteComposerSessionRegistry;

function createCursorStateDatabaseWithComposers(array $composers): string
{
    $dbPath = sys_get_temp_dir().'/cursor-stats-'.uniqid().'.vscdb';
    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->exec('CREATE TABLE ItemTable (key TEXT UNIQUE ON CONFLICT REPLACE, value BLOB)');

    $payload = json_encode(['allComposers' => $composers], JSON_THROW_ON_ERROR);
    $statement = $pdo->prepare('INSERT INTO ItemTable (key, value) VALUES (?, ?)');
    $statement->execute(['composer.composerHeaders', $payload]);
    $statement->execute(['cursorAuth/accessToken', testCursorAccessTokenJwt()]);

    return $dbPath;
}

it('reads composer sessions from sqlite registry', function () {
    $dbPath = createCursorStateDatabaseWithComposers([
        [
            'composerId' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Fix auth bug',
            'createdAt' => 1_000,
            'lastUpdatedAt' => 5_000,
            'unifiedMode' => 'agent',
            'workspaceIdentifier' => [
                'uri' => ['fsPath' => '/Users/dev/myproject'],
                'hash' => 'abc123',
            ],
        ],
    ]);

    config(['cursor_stats.sqlite_path' => $dbPath]);

    $sessions = (new SqliteComposerSessionRegistry)->listAll();

    expect($sessions)->toHaveCount(1)
        ->and($sessions[0]->composerId)->toBe('11111111-1111-1111-1111-111111111111')
        ->and($sessions[0]->name)->toBe('Fix auth bug')
        ->and($sessions[0]->createdAtMs)->toBe(1_000)
        ->and($sessions[0]->lastUpdatedAtMs)->toBe(5_000)
        ->and($sessions[0]->workspacePath)->toBe('/Users/dev/myproject')
        ->and($sessions[0]->workspaceHash)->toBe('abc123')
        ->and($sessions[0]->unifiedMode)->toBe('agent');

    unlink($dbPath);
});

it('returns an empty list when composer headers key is missing', function () {
    $dbPath = sys_get_temp_dir().'/cursor-stats-empty-headers-'.uniqid().'.vscdb';
    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->exec('CREATE TABLE ItemTable (key TEXT UNIQUE ON CONFLICT REPLACE, value BLOB)');
    $statement = $pdo->prepare('INSERT INTO ItemTable (key, value) VALUES (?, ?)');
    $statement->execute(['cursorAuth/accessToken', testCursorAccessTokenJwt()]);

    config(['cursor_stats.sqlite_path' => $dbPath]);

    expect((new SqliteComposerSessionRegistry)->listAll())->toBe([]);

    unlink($dbPath);
});
