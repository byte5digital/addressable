<?php

namespace Byte5\Addressable\App\Concerns;

use Byte5\Addressable\App\Contracts\Addressable;
use Byte5\Addressable\App\Contracts\CreatesAddresses;
use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Enums\AddressType;
use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\App\Support\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @phpstan-require-extends Model
 *
 * @phpstan-require-implements Addressable
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

    /**
     * Create and persist a new address for this model.
     *
     * Accepts either an AddressData DTO or a loose attribute array. The optional
     * $type parameter overrides whatever type is already set on the data.
     */
    public function addAddress(
        AddressData|array $data,
        AddressType|string|null $type = null,
    ): Address {
        $data = $data instanceof AddressData ? $data : AddressData::fromArray($data);

        if ($type !== null) {
            $data = $data->withType($type);
        }

        return app(CreatesAddresses::class)->create($this, $data);
    }
}
