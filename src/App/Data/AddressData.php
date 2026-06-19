<?php

namespace Byte5\Addressable\App\Data;

use BackedEnum;
use Byte5\Addressable\App\Enums\AddressType;

readonly class AddressData
{
    public function __construct(
        public AddressType|string|null $type = null,
        public ?string $street = null,
        public ?string $extra = null,
        public ?string $postal = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $country = null,
    ) {}

    /**
     * Build from a loose attribute array (unknown keys ignored).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function fromArray(array $attributes): self
    {
        $type = $attributes['type'] ?? null;

        return new self(
            type: $type instanceof AddressType ? $type : (is_string($type) ? $type : null),
            street: isset($attributes['street']) && is_string($attributes['street']) ? $attributes['street'] : null,
            extra: isset($attributes['extra']) && is_string($attributes['extra']) ? $attributes['extra'] : null,
            postal: isset($attributes['postal']) && is_string($attributes['postal']) ? $attributes['postal'] : null,
            city: isset($attributes['city']) && is_string($attributes['city']) ? $attributes['city'] : null,
            region: isset($attributes['region']) && is_string($attributes['region']) ? $attributes['region'] : null,
            latitude: isset($attributes['latitude']) && is_numeric($attributes['latitude'])
                ? (float) $attributes['latitude']
                : null,
            longitude: isset($attributes['longitude']) && is_numeric($attributes['longitude'])
                ? (float) $attributes['longitude']
                : null,
            country: isset($attributes['country']) && is_string($attributes['country']) ? $attributes['country'] : null,
        );
    }

    /**
     * Immutable copy with the address type set.
     */
    public function withType(AddressType|string $type): self
    {
        return new self(
            type: $type,
            street: $this->street,
            extra: $this->extra,
            postal: $this->postal,
            city: $this->city,
            region: $this->region,
            latitude: $this->latitude,
            longitude: $this->longitude,
            country: $this->country,
        );
    }

    /**
     * Column-ready array for `addresses()->create(...)`. `type` is normalised to
     * its scalar backing value so it works whether or not the model casts `type`
     * to an enum.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type instanceof BackedEnum ? $this->type->value : $this->type,
            'street' => $this->street,
            'extra' => $this->extra,
            'postal' => $this->postal,
            'city' => $this->city,
            'region' => $this->region,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'country' => $this->country,
        ];
    }
}
