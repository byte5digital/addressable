<?php

namespace Byte5\Addressable\App\Services;

use Byte5\Addressable\App\Contracts\Countries as CountriesContract;
use CommerceGuys\Addressing\Country\CountryRepository;
use League\ISO3166\ISO3166;

class Countries implements CountriesContract
{
    private static ?CountryRepository $repository = null;

    private static ?ISO3166 $iso3166 = null;

    /**
     * Country code => localised name, suitable for a dropdown.
     *
     * Names come from commerceguys (locale-aware, locale-collated) and are
     * restricted to official ISO 3166-1 alpha-2 codes, so every entry is a
     * valid selection that passes the Country rule.
     *
     * @return array<string, string>
     */
    public function list(?string $locale = null): array
    {
        $names = self::repository()->getList($locale);

        // Iterate the commerceguys list to keep its locale-specific ordering.
        return array_filter(
            $names,
            fn (string $code): bool => isset(self::isoCodes()[$code]),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Valid ISO 3166-1 alpha-2 codes, indexed for O(1) lookup.
     *
     * @return array<string, true>
     */
    private static function isoCodes(): array
    {
        $codes = [];

        foreach (self::iso3166()->all() as $country) {
            $codes[$country['alpha2']] = true;
        }

        return $codes;
    }

    private static function repository(): CountryRepository
    {
        return self::$repository ??= new CountryRepository;
    }

    private static function iso3166(): ISO3166
    {
        return self::$iso3166 ??= new ISO3166;
    }
}
