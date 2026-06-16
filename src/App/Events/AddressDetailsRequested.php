<?php

namespace Byte5\Addressable\App\Events;

class AddressDetailsRequested
{
    /**
     * Dispatched after a details() request completes (not on failure).
     *
     * @param  string  $provider  The lookup driver, e.g. `google`.
     * @param  string  $placeId  The looked-up place id.
     * @param  array<string, mixed>  $options  Per-call options passed to the driver.
     * @param  bool  $found  Whether a place was resolved (false when not found).
     */
    public function __construct(
        public string $provider,
        public string $placeId,
        public array $options,
        public bool $found,
    ) {}
}
