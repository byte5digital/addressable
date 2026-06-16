<?php

namespace Byte5\Addressable\App\Facades;

use Byte5\Addressable\App\Data\PlaceDetails;
use Byte5\Addressable\App\Data\Suggestion;
use Byte5\Addressable\App\Services\AddressLookupManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Suggestion[] suggest(string $query, array<string, mixed> $options = [])
 * @method static PlaceDetails|null details(string $placeId, array<string, mixed> $options = [])
 * @method static \Byte5\Addressable\App\Contracts\AddressLookup driver(string|null $driver = null)
 *
 * @see AddressLookupManager
 */
class AddressLookup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AddressLookupManager::class;
    }
}
