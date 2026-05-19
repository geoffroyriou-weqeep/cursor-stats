<?php

use App\Services\Cursor\Exceptions\CursorSessionUnavailableException;
use App\Services\Cursor\SessionCredential;

it('builds workos session cookie from sqlite access token jwt', function () {
    $jwt = testCursorAccessTokenJwt();

    $credential = SessionCredential::fromAccessToken($jwt);

    expect($credential->cookieHeader)->toBe('WorkosCursorSessionToken=user_01TEST::'.$jwt);
});

it('accepts composite session token from env', function () {
    $value = 'user_01TEST%3A%3A'.testCursorAccessTokenJwt();

    $credential = SessionCredential::fromTokenValue($value);

    expect($credential->cookieHeader)->toBe('WorkosCursorSessionToken='.$value);
});

it('throws when jwt sub claim is missing', function () {
    $header = rtrim(strtr(base64_encode('{"alg":"HS256"}'), '+/', '-_'), '=');
    $payload = rtrim(strtr(base64_encode('{"time":"123"}'), '+/', '-_'), '=');
    $jwt = $header.'.'.$payload.'.signature';

    SessionCredential::fromAccessToken($jwt);
})->throws(CursorSessionUnavailableException::class);
