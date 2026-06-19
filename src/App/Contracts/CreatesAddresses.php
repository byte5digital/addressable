<?php

namespace Byte5\Addressable\App\Contracts;

use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Models\Address;
use Illuminate\Database\Eloquent\Model;

interface CreatesAddresses
{
    /**
     * Persist a new address for the given owner model.
     *
     * @param  Model  $owner  A model using the HasAddresses trait.
     */
    public function create(Model $owner, AddressData $data): Address;
}
