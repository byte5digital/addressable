<?php

namespace Byte5\Addressable\App\Facades;

use Byte5\Addressable\App\Contracts\Countries as CountryContract;
use Illuminate\Support\Facades\Facade;

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
