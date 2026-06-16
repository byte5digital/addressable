<?php

namespace Byte5\Addressable\App\Contracts;

use Byte5\Addressable\App\Rules\AddressExists;
use Byte5\Addressable\App\Rules\Country;
use Byte5\Addressable\App\Rules\PostalFormat;

interface AddressRules
{
    /**
     * A postal code format rule for the given country.
     */
    public function postalFormat(?string $country): PostalFormat;

    /**
     * A rule asserting the value is a valid ISO 3166-1 alpha-2 country code.
     */
    public function country(): Country;

    /**
     * A rule asserting the full submitted address validates as deliverable
     * (calls the configured validation provider — one billable call per run).
     *
     * @param  array<string, string>  $fields  Override the default AddressInput field => input key map.
     */
    public function exists(array $fields = []): AddressExists;
}
