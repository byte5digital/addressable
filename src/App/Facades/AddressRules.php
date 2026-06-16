<?php

namespace Byte5\Addressable\App\Facades;

use Byte5\Addressable\App\Contracts\AddressRules as AddressRulesContract;
use Byte5\Addressable\App\Rules\Country;
use Byte5\Addressable\App\Rules\PostalFormat;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PostalFormat postalFormat(?string $country)
 * @method static Country country()
 *
 * @see \Byte5\Addressable\App\Contracts\AddressRules
 */
class AddressRules extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddressRulesContract::class;
    }
}
