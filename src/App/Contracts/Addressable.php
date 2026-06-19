<?php

namespace Byte5\Addressable\App\Contracts;

use Byte5\Addressable\App\Models\Address;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A model that owns addresses. Implemented by the HasAddresses trait; declare
 * `implements Addressable` on any model that uses the trait.
 */
interface Addressable
{
    /**
     * All addresses attached to this model.
     *
     * @return MorphMany<Address, Model>
     */
    public function addresses(): MorphMany;
}
