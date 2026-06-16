<?php

namespace Byte5\Addressable\App\Contracts;

interface LookupFactory
{
    /**
     * Resolve an address lookup driver by name (null resolves the default driver).
     */
    public function driver(?string $driver = null): AddressLookup;
}
