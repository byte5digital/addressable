<?php

use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\App\Enums\AddressType;

return [

    'models' => [

        /*
         * The Eloquent model used to store addresses. It must extend the package's
         * `Byte5\Addressable\App\Models\Address`. Swap in your own subclass (for
         * example one using the `HasUuids` or `HasUlids` trait) to match your
         * application's primary key strategy.
         */

        'address' => Address::class,
    ],

    'table_names' => [

        /*
         * The table used to store addresses. Change it BEFORE running the package
         * migration. The Address model reads this value too, so the schema and the
         * model stay in sync.
         */

        'addresses' => 'addresses',
    ],

    'column_names' => [

        /*
         * Name of the polymorphic key column that points at the owning model.
         *
         * For example, if the models that own addresses use UUID primary keys you
         * might name this `addressable_uuid`. The relationships read this value, so
         * the model and schema stay in sync.
         */

        'model_morph_key' => 'addressable_id',
    ],

    /*
     * The backed enum the `type` column is cast to. Replace it with your own
     * string-backed enum to use your application's address roles, or set it to
     * an empty string ('') to keep `type` a plain string.
     *
     * Note: the enum's backing values must match the values already stored in
     * the `type` column — switching the enum does not migrate existing data.
     */

    'type_enum' => AddressType::class,

    /*
     * Address autocomplete / geocoding. `provider` selects the driver resolved by
     * the AddressLookup manager (Google by default). Each provider has its own
     * credentials and request defaults.
     */

    'lookup' => [

        'provider' => env('ADDRESSABLE_LOOKUP_PROVIDER', 'google'),

        'providers' => [

            'google' => [
                'key' => env('ADDRESSABLE_LOOKUP_GOOGLE_KEY'),
                'language' => env('ADDRESSABLE_LOOKUP_GOOGLE_LANGUAGE'), // Places `languageCode`; falls back to app()->getLocale()
                'region' => env('ADDRESSABLE_LOOKUP_GOOGLE_REGION'),     // region bias (`regionCode`)
                'country' => env('ADDRESSABLE_LOOKUP_GOOGLE_COUNTRY'),   // single ISO 3166-1 alpha-2 code; set an array here for multiple
            ],

        ],
    ],

    /*
     * Address validation. `provider` selects the driver resolved by the
     * AddressValidation manager (Google by default). Google uses its Address
     * Validation API — a separate SKU that must be enabled in your Cloud project.
     */

    'validation' => [

        'provider' => env('ADDRESSABLE_VALIDATION_PROVIDER', 'google'),

        /*
         * How the AddressExists validation rule reacts when the provider cannot be
         * reached (network/outage, provider error, or missing API key). When false
         * (default) the AddressLookupException surfaces — a misconfiguration or
         * outage is treated as a server error rather than silently passing. Set it
         * to true to degrade gracefully and let the address through when the
         * provider is unavailable.
         */

        'pass_on_outage' => env('ADDRESSABLE_VALIDATION_PASS_ON_OUTAGE', false),

        'providers' => [

            'google' => [
                'key' => env('ADDRESSABLE_VALIDATION_GOOGLE_KEY'),
            ],

        ],
    ],
];
