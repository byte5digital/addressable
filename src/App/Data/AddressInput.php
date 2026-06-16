<?php

namespace Byte5\Addressable\App\Data;

readonly class AddressInput
{
    /**
     * A structured address to validate. All fields are optional; provide as much
     * as you have. `country` is the ISO 3166-1 alpha-2 code (Google `regionCode`).
     */
    public function __construct(
        public ?string $street = null,
        public ?string $postal = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?string $country = null,
    ) {}
}
