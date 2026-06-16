<?php

namespace Byte5\Addressable\App\Rules;

use Byte5\Addressable\App\Contracts\ValidatesAddresses;
use Byte5\Addressable\App\Data\AddressInput;
use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Byte5\Addressable\App\Support\Config;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Asserts that the submitted address validates as deliverable via the configured
 * validation provider. Reads the structured address from sibling fields of the
 * validation payload, so attach it to a single field — it makes one (billable)
 * provider call per validation run.
 */
class AddressExists implements DataAwareRule, ValidationRule
{
    /**
     * The full data under validation.
     *
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * Map of AddressInput field => input key (dot notation supported).
     *
     * @var array<string, string>
     */
    protected array $fields;

    /**
     * @param  array<string, string>  $fields  Override the default field mapping.
     */
    public function __construct(array $fields = [])
    {
        $this->fields = array_merge([
            'street' => 'street',
            'postal' => 'postal',
            'city' => 'city',
            'region' => 'region',
            'country' => 'country',
        ], $fields);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $address = new AddressInput(
            street: $this->field('street'),
            postal: $this->field('postal'),
            city: $this->field('city'),
            region: $this->field('region'),
            country: $this->field('country'),
        );

        // Nothing to check — leave emptiness to required/nullable and skip the billable provider call.
        if ($this->isEmpty($address)) {
            return;
        }

        try {
            $valid = app(ValidatesAddresses::class)->validate($address)->valid;
        } catch (AddressLookupException $e) {
            // Provider unreachable: pass when configured to degrade gracefully, otherwise surface the error.
            if (Config::validationPassesOnOutage()) {
                return;
            }

            throw $e;
        }

        if (! $valid) {
            $fail('byte5-addressable::validation.address_exists')->translate(['attribute' => $attribute]);
        }
    }

    private function field(string $key): ?string
    {
        $value = data_get($this->data, $this->fields[$key]);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function isEmpty(AddressInput $address): bool
    {
        return $address->street === null
            && $address->postal === null
            && $address->city === null
            && $address->region === null
            && $address->country === null;
    }
}
