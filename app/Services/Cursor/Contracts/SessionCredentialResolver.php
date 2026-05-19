<?php

namespace App\Services\Cursor\Contracts;

use App\Services\Cursor\SessionCredential;

interface SessionCredentialResolver
{
    public function resolve(): SessionCredential;
}
