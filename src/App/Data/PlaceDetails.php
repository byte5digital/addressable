<?php

namespace Byte5\Addressable\App\Data;

readonly class PlaceDetails
{
    public function __construct(
        public ?string $street = null,
        public ?string $extra = null,
        public ?string $postal = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?string $country = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /**
     * Address columns, ready for `addresses()->create(...)`.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'extra' => $this->extra,
            'postal' => $this->postal,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
