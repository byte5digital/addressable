<?php

namespace Byte5\Addressable\App\Services\Drivers\Google;

use Saloon\Http\Connector;

/**
 * Base Saloon connector for Google Maps Platform APIs: authenticates every
 * request with the project API key via the X-Goog-Api-Key header.
 */
abstract class GoogleConnector extends Connector
{
    public function __construct(protected readonly string $apiKey) {}

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return ['X-Goog-Api-Key' => $this->apiKey];
    }
}
