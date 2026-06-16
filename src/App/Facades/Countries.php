<?php

namespace Byte5\Addressable\App\Facades;

use Illuminate\Support\Facades\Facade;
use Byte5\Addressable\App\Contracts\Countries as CountryContract;

/**
 * @method static array<string, string> list(?string $locale = null)
 *
 * @see CountryContract
 */
class Countries extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CountryContract::class;
    }
}
