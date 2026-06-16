<?php

namespace Byte5\Addressable\App\Services\Drivers\Google\Places\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class PlaceDetailsRequest extends Request
{
    protected Method $method = Method::GET;

    private const FIELD_MASK = 'id,formattedAddress,addressComponents,location';

    /**
     * @param  array<string, mixed>  $queryParameters
     */
    public function __construct(
        protected readonly string $placeId,
        protected readonly array $queryParameters,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/v1/places/'.$this->placeId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultQuery(): array
    {
        return $this->queryParameters;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return ['X-Goog-FieldMask' => self::FIELD_MASK];
    }
}
