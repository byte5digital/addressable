<?php

namespace Byte5\Addressable\App\Events;

use Byte5\Addressable\App\Data\AddressInput;

class AddressValidationRequested
{
    /**
     * Dispatched after a validate() request completes (not on failure).
     *
     * @param  string  $provider  The validation driver, e.g. `google`.
     * @param  AddressInput  $address  The address that was validated.
     * @param  array<string, mixed>  $options  Per-call options passed to the driver.
     * @param  bool  $valid  The validation verdict.
     */
    public function __construct(
        public string $provider,
        public AddressInput $address,
        public array $options,
        public bool $valid,
    ) {}
}
