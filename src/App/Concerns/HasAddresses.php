<?php

namespace Byte5\Addressable\App\Concerns;

use Byte5\Addressable\App\Support\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @phpstan-require-extends Model
 */
trait HasAddresses
{
    /**
     * All addresses attached to this model.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(
            Config::addressModel(),
            'addressable',
            'addressable_type',
            Config::morphKey(),
        );
    }

    /**
     * The most recently attached address.
     */
    public function latestAddress(): MorphOne
    {
        return $this->morphOne(
            Config::addressModel(),
            'addressable',
            'addressable_type',
            Config::morphKey(),
        )->latestOfMany();
    }
}
