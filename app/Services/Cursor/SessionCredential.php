<?php

namespace App\Services\Cursor;

use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;

final readonly class SessionCredential
{
    public function __construct(
        public string $cookieHeader,
    ) {}

    public static function fromTokenValue(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new CursorSessionUnavailableException(
                'CURSOR_SESSION_COOKIE is empty.',
            );
        }

        if (str_starts_with($trimmed, 'WorkosCursorSessionToken=')) {
            return new self($trimmed);
        }

        if (self::isCompositeSessionToken($trimmed)) {
            return new self('WorkosCursorSessionToken='.$trimmed);
        }

        if (str_starts_with($trimmed, 'eyJ')) {
            return self::fromAccessToken($trimmed);
        }

        return new self('WorkosCursorSessionToken='.$trimmed);
    }

    public static function fromAccessToken(string $accessToken): self
    {
        $jwt = trim($accessToken);

        if ($jwt === '') {
            throw new CursorSessionUnavailableException(
                'Cursor access token is empty.',
            );
        }

        $userId = self::extractUserIdFromJwt($jwt);

        return new self('WorkosCursorSessionToken='.$userId.'::'.$jwt);
    }

    private static function isCompositeSessionToken(string $value): bool
    {
        return str_contains($value, '::')
            || str_contains($value, '%3A%3A')
            || (str_starts_with($value, 'user_') && str_contains($value, 'eyJ'));
    }

    private static function extractUserIdFromJwt(string $jwt): string
    {
        $parts = explode('.', $jwt);

        if (count($parts) < 2) {
            throw new CursorSessionUnavailableException(
                'Cursor access token is not a valid JWT.',
            );
        }

        $payload = json_decode(self::base64UrlDecode($parts[1]), true);

        if (! is_array($payload)) {
            throw new CursorSessionUnavailableException(
                'Cursor access token JWT payload is invalid.',
            );
        }

        $subject = $payload['sub'] ?? null;

        if (! is_string($subject) || $subject === '') {
            throw new CursorSessionUnavailableException(
                'Cursor access token JWT is missing a sub claim.',
            );
        }

        if (str_contains($subject, '|')) {
            return substr($subject, strrpos($subject, '|') + 1);
        }

        return $subject;
    }

    private static function base64UrlDecode(string $value): string
    {
        $padded = strtr($value, '-_', '+/');
        $padding = strlen($padded) % 4;

        if ($padding > 0) {
            $padded .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($padded, true);

        if ($decoded === false) {
            throw new CursorSessionUnavailableException(
                'Cursor access token JWT payload could not be decoded.',
            );
        }

        return $decoded;
    }
}
