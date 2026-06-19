<?php

namespace Byte5\Addressable\App\Data;

readonly class PostalAddress
{
    public function __construct(
        public ?string $street = null,
        public ?string $extra = null,
        public ?string $postal = null,
        public ?string $city = null,
        public ?string $region = null,
        public ?string $country = null,
    ) {}

    /**
     * schema.org PostalAddress as a nestable fragment (`@type`, no `@context`).
     * Null fields are omitted.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $this->street,
            'extendedAddress' => $this->extra,
            'postalCode' => $this->postal,
            'addressLocality' => $this->city,
            'addressRegion' => $this->region,
            'addressCountry' => $this->country,
        ], fn (?string $value): bool => $value !== null);
    }

    /**
     * Standalone JSON-LD document (includes `@context`), ready for a
     * `<script type="application/ld+json">` tag.
     */
    public function toJsonLd(): string
    {
        return json_encode(
            ['@context' => 'https://schema.org'] + $this->toArray(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
