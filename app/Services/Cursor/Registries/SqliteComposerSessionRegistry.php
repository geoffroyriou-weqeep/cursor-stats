<?php

namespace App\Services\Cursor\Registries;

use App\Services\Cursor\Contracts\ComposerSessionRegistry;
use App\Services\Cursor\Dto\ComposerSessionDto;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\Resolvers\SqliteSessionCredentialResolver;
use PDO;

final class SqliteComposerSessionRegistry implements ComposerSessionRegistry
{
    private const COMPOSER_HEADERS_KEY = 'composer.composerHeaders';

    public function listAll(): array
    {
        $path = SqliteSessionCredentialResolver::databasePath();

        if (! is_readable($path)) {
            throw new CursorSessionUnavailableException(
                'Cursor local database not found or not readable at: '.$path,
            );
        }

        $pdo = new PDO('sqlite:'.$path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $statement = $pdo->prepare('SELECT value FROM ItemTable WHERE key = ?');
        $statement->execute([self::COMPOSER_HEADERS_KEY]);
        $raw = $statement->fetchColumn();

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $composers = $decoded['allComposers'] ?? [];

        if (! is_array($composers)) {
            return [];
        }

        $sessions = [];

        foreach ($composers as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $session = $this->mapEntry($entry);

            if ($session !== null) {
                $sessions[] = $session;
            }
        }

        return $sessions;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function mapEntry(array $entry): ?ComposerSessionDto
    {
        $composerId = $entry['composerId'] ?? null;

        if (! is_string($composerId) || $composerId === '') {
            return null;
        }

        $createdAt = $entry['createdAt'] ?? $entry['createdAtMs'] ?? null;

        if (! is_numeric($createdAt)) {
            return null;
        }

        $lastUpdatedAt = $entry['lastUpdatedAt'] ?? $entry['lastUpdatedAtMs'] ?? null;
        $workspaceIdentifier = is_array($entry['workspaceIdentifier'] ?? null)
            ? $entry['workspaceIdentifier']
            : [];
        $uri = is_array($workspaceIdentifier['uri'] ?? null)
            ? $workspaceIdentifier['uri']
            : [];
        $workspacePath = $uri['fsPath'] ?? $entry['workspacePath'] ?? null;
        $workspaceHash = $workspaceIdentifier['hash']
            ?? $entry['workspaceHash']
            ?? null;
        $unifiedMode = $entry['unifiedMode'] ?? 'chat';

        return new ComposerSessionDto(
            composerId: $composerId,
            name: is_string($entry['name'] ?? null) ? $entry['name'] : null,
            createdAtMs: (int) $createdAt,
            lastUpdatedAtMs: is_numeric($lastUpdatedAt) ? (int) $lastUpdatedAt : null,
            workspacePath: is_string($workspacePath) ? $workspacePath : null,
            workspaceHash: is_string($workspaceHash) ? $workspaceHash : null,
            unifiedMode: is_string($unifiedMode) ? $unifiedMode : 'chat',
        );
    }
}
