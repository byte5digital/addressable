<?php

namespace Byte5\Addressable\App\Services\Drivers\Concerns;

use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * Shared Google HTTP plumbing for the lookup and validation drivers.
 */
trait InteractsWithGoogle
{
    protected const PROVIDER = 'google';

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(protected readonly array $config) {}

    /**
     * The env var that supplies this driver's API key (named in the missing-key error).
     */
    abstract protected function apiKeyEnv(): string;

    /**
     * Send a Saloon request, converting connection-level failures into AddressLookupException.
     */
    protected function send(Connector $connector, Request $request): Response
    {
        try {
            return $connector->send($request);
        } catch (FatalRequestException $e) {
            throw AddressLookupException::connectionFailed(self::PROVIDER, $e);
        }
    }

    protected function apiKey(): string
    {
        $key = $this->config['key'] ?? null;

        if (! is_string($key) || $key === '') {
            throw AddressLookupException::missingApiKey($this->apiKeyEnv());
        }

        return $key;
    }

    protected function throwIfFailed(Response $response): void
    {
        if ($response->failed()) {
            throw AddressLookupException::requestFailed(self::PROVIDER, $response->status(), $response->body());
        }
    }
}
