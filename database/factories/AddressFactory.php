<?php

namespace Byte5\Addressable\Database\Factories;

use Byte5\Addressable\App\Enums\AddressType;
use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\App\Support\Config;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * @return class-string<Address>
     */
    public function modelName(): string
    {
        return Config::addressModel();
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(AddressType::cases()),
            'street' => fake()->streetAddress(),
            'extra' => 'Apt. '.fake()->numberBetween(1, 999),
            'postal' => fake()->postcode(),
            'city' => fake()->city(),
            // `state()` only exists in some Faker locales; `city()` is on the base
            // provider, so the factory works regardless of app.faker_locale.
            'region' => fake()->city(),
            'country' => fake()->countryCode(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
