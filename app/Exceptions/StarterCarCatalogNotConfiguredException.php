<?php

namespace App\Exceptions;

use RuntimeException;

class StarterCarCatalogNotConfiguredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Starter car catalog is not configured. Run php artisan db:seed.',
        );
    }
}
