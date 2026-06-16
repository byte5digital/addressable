<?php

namespace Byte5\Addressable\App\Services;

use Byte5\Addressable\App\Contracts\AddressLookup;
use Byte5\Addressable\App\Contracts\LookupFactory;
use Byte5\Addressable\App\Services\Drivers\GoogleLookupDriver;
use Byte5\Addressable\App\Support\Config;
use Illuminate\Support\Manager;

class AddressLookupManager extends Manager implements LookupFactory
{
    public function getDefaultDriver(): string
    {
        return Config::lookupProvider();
    }

    public function driver($driver = null): AddressLookup
    {
        $resolved = parent::driver($driver);

        if (! $resolved instanceof AddressLookup) {
            throw new \LogicException('The resolved lookup driver must implement '.AddressLookup::class.'.');
        }

        return $resolved;
    }

    protected function createGoogleDriver(): GoogleLookupDriver
    {
        return new GoogleLookupDriver(Config::googleConfig());
    }
}
