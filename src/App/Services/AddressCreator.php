<?php

namespace Byte5\Addressable\App\Services;

use Byte5\Addressable\App\Contracts\Addressable;
use Byte5\Addressable\App\Contracts\CreatesAddresses;
use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Models\Address;
use Illuminate\Database\Eloquent\Model;

final class AddressCreator implements CreatesAddresses
{
    public function create(Model&Addressable $owner, AddressData $data): Address
    {
        return $owner->addresses()->create($data->toArray());
    }
}
