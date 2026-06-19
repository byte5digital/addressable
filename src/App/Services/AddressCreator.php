<?php

namespace Byte5\Addressable\App\Services;

use Byte5\Addressable\App\Contracts\CreatesAddresses;
use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\App\Support\Config;
use Illuminate\Database\Eloquent\Model;

final class AddressCreator implements CreatesAddresses
{
    public function create(Model $owner, AddressData $data): Address
    {
        /** @var Address $address */
        $address = Config::addressesRelation($owner)->create($data->toArray());

        return $address;
    }
}
