<?php

namespace App\Services\Cursor\Contracts;

use App\Services\Cursor\Dto\SessionCredential;

interface SessionCredentialResolver
{
    public function resolve(): SessionCredential;
}
