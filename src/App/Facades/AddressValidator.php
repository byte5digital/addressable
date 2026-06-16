<?php

namespace Byte5\Addressable\App\Facades;

use Byte5\Addressable\App\Data\AddressInput;
use Byte5\Addressable\App\Data\AddressValidation;
use Byte5\Addressable\App\Services\AddressValidationManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static AddressValidation validate(AddressInput $address, array<string, mixed> $options = [])
 * @method static \Byte5\Addressable\App\Contracts\ValidatesAddresses driver(string|null $driver = null)
 *
 * @see AddressValidationManager
 */
class AddressValidator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddressValidationManager::class;
    }
}
