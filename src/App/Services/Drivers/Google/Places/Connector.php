<?php

namespace Byte5\Addressable\App\Services\Drivers\Google\Places;

use Byte5\Addressable\App\Services\Drivers\Google\GoogleConnector;

class Connector extends GoogleConnector
{
    public function resolveBaseUrl(): string
    {
        return 'https://places.googleapis.com';
    }
}
