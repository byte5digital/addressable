<?php

namespace Byte5\Addressable\App\Data;

readonly class AddressValidation
{
    /**
     * Provider-agnostic result of validating an address.
     *
     * @param  bool  $valid  Normalised: the address is deliverable / exists, per the provider's verdict.
     * @param  string  $provider  The driver that produced this result, e.g. `google`.
     * @param  string|null  $formattedAddress  The provider's corrected/standardised address.
     * @param  array<string, mixed>  $raw  The provider's full response payload. Provider-specific fields
     *                                     (e.g. Google's `verdict`/`geocode`) live here.
     */
    public function __construct(
        public bool $valid,
        public string $provider,
        public ?string $formattedAddress,
        public array $raw = [],
    ) {}
}
