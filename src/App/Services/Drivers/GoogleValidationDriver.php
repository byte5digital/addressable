<?php

namespace Byte5\Addressable\App\Services\Drivers;

use Byte5\Addressable\App\Contracts\ValidatesAddresses;
use Byte5\Addressable\App\Data\AddressInput;
use Byte5\Addressable\App\Data\GoogleAddressValidation;
use Byte5\Addressable\App\Events\AddressValidationRequested;
use Byte5\Addressable\App\Services\Drivers\Concerns\InteractsWithGoogle;
use Byte5\Addressable\App\Services\Drivers\Google\Validation\Connector as AddressValidationConnector;
use Byte5\Addressable\App\Services\Drivers\Google\Validation\Requests\ValidateAddressRequest;
use Illuminate\Support\Facades\Event;

class GoogleValidationDriver implements ValidatesAddresses
{
    use InteractsWithGoogle;

    protected function apiKeyEnv(): string
    {
        return 'ADDRESSABLE_VALIDATION_GOOGLE_KEY';
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function validate(AddressInput $address, array $options = []): GoogleAddressValidation
    {
        $body = [
            'address' => array_filter([
                'regionCode' => $address->country,
                'addressLines' => $this->addressLines($address),
            ], fn ($value): bool => $value !== null && $value !== []),
        ];

        $connector = new AddressValidationConnector($this->apiKey());
        $response = $this->send($connector, new ValidateAddressRequest($body));

        $this->throwIfFailed($response);

        $result = $response->json('result', []);
        $validation = $this->toAddressValidation(is_array($result) ? $result : []);

        Event::dispatch(new AddressValidationRequested(self::PROVIDER, $address, $options, $validation->valid));

        return $validation;
    }

    /**
     * Build Address Validation `addressLines` from the structured input.
     *
     * @return string[]
     */
    private function addressLines(AddressInput $address): array
    {
        $locality = trim(implode(' ', array_filter([
            $address->postal,
            $address->city,
        ], fn (?string $value): bool => $value !== null && $value !== '')));

        return array_values(array_filter([
            $address->street,
            $locality !== '' ? $locality : null,
            $address->region,
        ], fn (?string $value): bool => $value !== null && $value !== ''));
    }

    /**
     * Map the Address Validation `result` into a GoogleAddressValidation (the typed
     * Google subclass of the provider-agnostic AddressValidation). The address is
     * treated as valid when validated to PREMISE/SUB_PREMISE granularity, the address
     * is complete, and no components are unconfirmed. The full verdict/geocode is
     * preserved in `raw`.
     *
     * @param  array<string, mixed>  $result
     */
    private function toAddressValidation(array $result): GoogleAddressValidation
    {
        $verdict = $result['verdict'] ?? [];
        $verdict = is_array($verdict) ? $verdict : [];

        $granularity = $verdict['validationGranularity'] ?? 'GRANULARITY_UNSPECIFIED';
        $granularity = is_string($granularity) ? $granularity : 'GRANULARITY_UNSPECIFIED';
        $complete = (bool) ($verdict['addressComplete'] ?? false);
        $hasUnconfirmed = (bool) ($verdict['hasUnconfirmedComponents'] ?? false);

        $formattedAddress = data_get($result, 'address.formattedAddress');

        return new GoogleAddressValidation(
            valid: in_array($granularity, ['PREMISE', 'SUB_PREMISE'], true) && $complete && ! $hasUnconfirmed,
            formattedAddress: is_string($formattedAddress) ? $formattedAddress : null,
            granularity: $granularity,
            complete: $complete,
            hasUnconfirmedComponents: $hasUnconfirmed,
            hasInferredComponents: (bool) ($verdict['hasInferredComponents'] ?? false),
            hasReplacedComponents: (bool) ($verdict['hasReplacedComponents'] ?? false),
            raw: $result,
        );
    }
}
