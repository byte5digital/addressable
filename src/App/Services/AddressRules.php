<?php

namespace Byte5\Addressable\App\Services;

use Byte5\Addressable\App\Contracts\AddressRules as AddressRulesContract;
use Byte5\Addressable\App\Rules\AddressExists;
use Byte5\Addressable\App\Rules\Country;
use Byte5\Addressable\App\Rules\PostalFormat;

class AddressRules implements AddressRulesContract
{
    public function postalFormat(?string $country): PostalFormat
    {
        return new PostalFormat($country);
    }

    public function country(): Country
    {
        return new Country();
    }

    /**
     * @param  array<string, string>  $fields
     */
    public function exists(array $fields = []): AddressExists
    {
        return new AddressExists($fields);
    }
}
