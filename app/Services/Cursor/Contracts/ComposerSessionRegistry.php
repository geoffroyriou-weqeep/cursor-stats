<?php

namespace App\Services\Cursor\Contracts;

use App\Services\Cursor\Dto\ComposerSessionDto;

interface ComposerSessionRegistry
{
    /**
     * @return list<ComposerSessionDto>
     */
    public function listAll(): array;
}
