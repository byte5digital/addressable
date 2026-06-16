<?php

namespace Byte5\Addressable\App\Contracts;

use Byte5\Addressable\App\Data\PlaceDetails;
use Byte5\Addressable\App\Data\Suggestion;
use Byte5\Addressable\App\Exceptions\AddressLookupException;

interface AddressLookup
{
    /**
     * Address predictions for a free-text query.
     *
     * @param  array<string, mixed>  $options  Per-call overrides (language, region, country, sessionToken).
     * @return Suggestion[]
     *
     * @throws AddressLookupException When the provider is misconfigured (e.g. missing API key), cannot be reached, or responds with an error status.
     */
    public function suggest(string $query, array $options = []): array;

    /**
     * Resolve a prediction (place id) into a structured address, or null if not found.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws AddressLookupException When the provider is misconfigured (e.g. missing API key), cannot be reached, or responds with an error status.
     */
    public function details(string $placeId, array $options = []): ?PlaceDetails;
}
