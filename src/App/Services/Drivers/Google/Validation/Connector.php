<?php

namespace Byte5\Addressable\App\Services\Drivers\Google\Validation;

use Byte5\Addressable\App\Services\Drivers\Google\GoogleConnector;

class Connector extends GoogleConnector
{
    public function resolveBaseUrl(): string
    {
        return 'https://addressvalidation.googleapis.com';
    }
}
