<?php

namespace Byte5\Addressable\App\Support;

use Byte5\Addressable\App\Models\Address;
use Illuminate\Support\Facades\Config as ConfigFacade;

class Config
{
    /**
     * @return class-string<Address>
     */
    public static function addressModel(): string
    {
        /** @var class-string<Address> */
        return ConfigFacade::string('byte5-addressable.models.address', Address::class);
    }

    public static function addressesTable(): string
    {
        return ConfigFacade::string('byte5-addressable.table_names.addresses', 'addresses');
    }

    public static function morphKey(): string
    {
        return ConfigFacade::string('byte5-addressable.column_names.model_morph_key', 'addressable_id');
    }

    public static function typeEnum(): string
    {
        return ConfigFacade::string('byte5-addressable.type_enum', '');
    }

    public static function lookupProvider(): string
    {
        return ConfigFacade::string('byte5-addressable.lookup.provider', 'google');
    }

    /**
     * @return array<string, mixed>
     */
    public static function googleConfig(): array
    {
        return ConfigFacade::array('byte5-addressable.lookup.providers.google', []);
    }

    public static function validationProvider(): string
    {
        return ConfigFacade::string('byte5-addressable.validation.provider', 'google');
    }

    /**
     * Whether the AddressExists rule should pass (rather than throw) when the
     * validation provider cannot be reached.
     */
    public static function validationPassesOnOutage(): bool
    {
        return ConfigFacade::boolean('byte5-addressable.validation.pass_on_outage', false);
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationGoogleConfig(): array
    {
        return ConfigFacade::array('byte5-addressable.validation.providers.google', []);
    }
}
