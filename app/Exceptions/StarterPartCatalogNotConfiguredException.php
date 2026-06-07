<?php

namespace App\Exceptions;

use RuntimeException;

class StarterPartCatalogNotConfiguredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'Starter part catalog is not configured. Run php artisan db:seed.',
        );
    }
}
