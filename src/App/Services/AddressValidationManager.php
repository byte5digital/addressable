<?php

namespace Byte5\Addressable\App\Services;

use Byte5\Addressable\App\Contracts\ValidatesAddresses;
use Byte5\Addressable\App\Contracts\ValidationFactory;
use Byte5\Addressable\App\Services\Drivers\GoogleValidationDriver;
use Byte5\Addressable\App\Support\Config;
use Illuminate\Support\Manager;

class AddressValidationManager extends Manager implements ValidationFactory
{
    public function getDefaultDriver(): string
    {
        return Config::validationProvider();
    }

    public function driver($driver = null): ValidatesAddresses
    {
        $resolved = parent::driver($driver);

        if (! $resolved instanceof ValidatesAddresses) {
            throw new \LogicException('The resolved validation driver must implement '.ValidatesAddresses::class.'.');
        }

        return $resolved;
    }

    protected function createGoogleDriver(): GoogleValidationDriver
    {
        return new GoogleValidationDriver(Config::validationGoogleConfig());
    }
}
