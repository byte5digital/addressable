<?php

namespace Byte5\Addressable\App\Contracts;

use Byte5\Addressable\App\Data\AddressInput;
use Byte5\Addressable\App\Data\AddressValidation;
use Byte5\Addressable\App\Exceptions\AddressLookupException;

interface ValidatesAddresses
{
    /**
     * Validate whether a structured address actually exists / is deliverable.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws AddressLookupException When the provider is misconfigured (e.g. missing API key), cannot be reached, or responds with an error status.
     */
    public function validate(AddressInput $address, array $options = []): AddressValidation;
}
