<?php

namespace App\Domain\Tenancy\Exceptions;

use LogicException;

final class MissingTenantContext extends LogicException
{
    public function __construct()
    {
        parent::__construct('A tenant-scoped model was queried without an active tenant context.');
    }
}
