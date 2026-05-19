<?php

namespace App\Services\Cursor;

final readonly class SessionCredential
{
    public function __construct(
        public string $cookieHeader,
    ) {}

    public static function fromTokenValue(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new Exceptions\CursorSessionUnavailableException(
                'CURSOR_SESSION_COOKIE is empty.',
            );
        }

        if (str_contains($trimmed, '=')) {
            return new self($trimmed);
        }

        return new self('WorkosCursorSessionToken='.$trimmed);
    }
}
