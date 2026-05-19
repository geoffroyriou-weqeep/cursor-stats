<?php

namespace App\Services\Cursor\Resolvers;

use App\Services\Cursor\Contracts\SessionCredentialResolver;
use App\Services\Cursor\Dto\SessionCredential;
use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;

final class CompositeSessionCredentialResolver implements SessionCredentialResolver
{
    /**
     * @param  list<SessionCredentialResolver>  $resolvers
     */
    public function __construct(
        private readonly array $resolvers,
    ) {}

    public function resolve(): SessionCredential
    {
        $lastException = null;

        foreach ($this->resolvers as $resolver) {
            try {
                return $resolver->resolve();
            } catch (CursorSessionUnavailableException $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException ?? new CursorSessionUnavailableException(
            'No session credential available from Cursor SQLite or .env.',
        );
    }
}
