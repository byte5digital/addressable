<?php

namespace Byte5\Addressable\App\Contracts;

interface ValidationFactory
{
    /**
     * Resolve an address validation driver by name (null resolves the default driver).
     */
    public function driver(?string $driver = null): ValidatesAddresses;
}
