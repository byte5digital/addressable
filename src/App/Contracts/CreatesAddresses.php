<?php

namespace Byte5\Addressable\App\Contracts;

use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Models\Address;
use Illuminate\Database\Eloquent\Model;

interface CreatesAddresses
{
    /**
     * Persist a new address for the given owner model.
     */
    public function create(Model&Addressable $owner, AddressData $data): Address;
}
