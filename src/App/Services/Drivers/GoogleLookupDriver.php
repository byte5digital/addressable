<?php

namespace Byte5\Addressable\App\Services\Drivers;

use Byte5\Addressable\App\Contracts\AddressLookup;
use Byte5\Addressable\App\Data\PlaceDetails;
use Byte5\Addressable\App\Data\Suggestion;
use Byte5\Addressable\App\Events\AddressDetailsRequested;
use Byte5\Addressable\App\Events\AddressResolved;
use Byte5\Addressable\App\Events\AddressSuggestionsRequested;
use Byte5\Addressable\App\Services\Drivers\Concerns\InteractsWithGoogle;
use Byte5\Addressable\App\Services\Drivers\Google\Places\Connector as PlacesConnector;
use Byte5\Addressable\App\Services\Drivers\Google\Places\Requests\AutocompleteRequest;
use Byte5\Addressable\App\Services\Drivers\Google\Places\Requests\PlaceDetailsRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

class GoogleLookupDriver implements AddressLookup
{
    use InteractsWithGoogle;

    protected function apiKeyEnv(): string
    {
        return 'ADDRESSABLE_LOOKUP_GOOGLE_KEY';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return Suggestion[]
     */
    public function suggest(string $query, array $options = []): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $body = array_filter([
            'input' => $query,
            'languageCode' => $this->language($options),
            'regionCode' => $options['region'] ?? $this->config['region'] ?? null,
            'includedRegionCodes' => $this->countries($options),
            'sessionToken' => $options['sessionToken'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== []);

        $connector = new PlacesConnector($this->apiKey());
        $response = $this->send($connector, new AutocompleteRequest($body));

        $this->throwIfFailed($response);

        $suggestions = $response->json('suggestions', []);
        $suggestions = is_array($suggestions) ? $suggestions : [];

        $results = array_values(array_filter(array_map(
            fn (mixed $suggestion): ?Suggestion => $this->toSuggestion($suggestion),
            $suggestions,
        )));

        Event::dispatch(new AddressSuggestionsRequested(self::PROVIDER, $query, $options, count($results)));

        return $results;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function details(string $placeId, array $options = []): ?PlaceDetails
    {
        $query = array_filter([
            'languageCode' => $this->language($options),
            'sessionToken' => $options['sessionToken'] ?? null,
        ], fn ($value): bool => $value !== null);

        $connector = new PlacesConnector($this->apiKey());
        $response = $this->send($connector, new PlaceDetailsRequest($placeId, $query));

        if ($response->status() === 404) {
            Event::dispatch(new AddressDetailsRequested(self::PROVIDER, $placeId, $options, found: false));

            return null;
        }

        $this->throwIfFailed($response);

        $details = $this->toPlaceDetails($response->json());

        Event::dispatch(new AddressDetailsRequested(self::PROVIDER, $placeId, $options, found: true));
        Event::dispatch(new AddressResolved(self::PROVIDER, $placeId, $details));

        return $details;
    }

    private function toSuggestion(mixed $suggestion): ?Suggestion
    {
        $placeId = data_get($suggestion, 'placePrediction.placeId');

        if (! is_string($placeId) || $placeId === '') {
            return null;
        }

        return new Suggestion(
            placeId: $placeId,
            description: $this->string($suggestion, 'placePrediction.text.text'),
            mainText: $this->string($suggestion, 'placePrediction.structuredFormat.mainText.text'),
            secondaryText: $this->string($suggestion, 'placePrediction.structuredFormat.secondaryText.text'),
        );
    }

    /**
     * @param  array<array-key, mixed>  $place
     */
    private function toPlaceDetails(array $place): PlaceDetails
    {
        $components = $place['addressComponents'] ?? [];
        $latitude = data_get($place, 'location.latitude');
        $longitude = data_get($place, 'location.longitude');

        return new PlaceDetails(
            street: $this->streetLine($place['formattedAddress'] ?? null),
            extra: $this->component($components, ['subpremise']),
            postal: $this->component($components, ['postal_code']),
            city: $this->component($components, ['locality', 'postal_town', 'sublocality']),
            region: $this->component($components, ['administrative_area_level_1']),
            country: $this->component($components, ['country'], short: true),
            latitude: is_numeric($latitude) ? (float) $latitude : null,
            longitude: is_numeric($longitude) ? (float) $longitude : null,
        );
    }

    /**
     * The street line (number + route, locale-ordered) is the first segment of
     * Google's formatted address, e.g. "Pariser Platz 1, 10117 Berlin, Germany".
     */
    private function streetLine(mixed $formattedAddress): ?string
    {
        if (! is_string($formattedAddress)) {
            return null;
        }

        $street = trim(explode(',', $formattedAddress)[0]);

        return $street === '' ? null : $street;
    }

    /**
     * First matching component, in `$types` priority order.
     *
     * @param  string[]  $types
     */
    private function component(mixed $components, array $types, bool $short = false): ?string
    {
        if (! is_array($components)) {
            return null;
        }

        foreach ($types as $type) {
            foreach ($components as $component) {
                if (! is_array($component)) {
                    continue;
                }

                $componentTypes = $component['types'] ?? [];

                if (is_array($componentTypes) && in_array($type, $componentTypes, true)) {
                    $text = $short
                        ? ($component['shortText'] ?? $component['longText'] ?? null)
                        : ($component['longText'] ?? $component['shortText'] ?? null);

                    return is_string($text) ? $text : null;
                }
            }
        }

        return null;
    }

    /**
     * Read a string at a dot-path from decoded JSON, or '' when absent or non-string.
     */
    private function string(mixed $data, string $path): string
    {
        $value = data_get($data, $path);

        return is_string($value) ? $value : '';
    }

    /**
     * Resolve the Places `languageCode`: per-call option, then provider config,
     * falling back to the application locale when neither is set.
     *
     * @param  array<string, mixed>  $options
     */
    private function language(array $options): string
    {
        $language = $options['language'] ?? $this->config['language'] ?? null;

        return is_string($language) && $language !== ''
            ? $language
            : App::getLocale();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return string[]
     */
    private function countries(array $options): array
    {
        $countries = $options['country'] ?? $this->config['country'] ?? null;

        if ($countries === null || $countries === []) {
            return [];
        }

        $list = is_array($countries) ? $countries : [$countries];

        return array_values(array_filter(
            array_map(fn (mixed $code): ?string => is_scalar($code) ? (string) $code : null, $list),
            fn (?string $code): bool => $code !== null,
        ));
    }
}
