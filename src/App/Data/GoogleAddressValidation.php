<?php

namespace Byte5\Addressable\App\Data;

readonly class GoogleAddressValidation extends AddressValidation
{
    /**
     * Google's Address Validation result, with the typed `verdict` fields on top of
     * the provider-agnostic base.
     *
     * @param  string  $granularity  Google `validationGranularity` (PREMISE, SUB_PREMISE, ROUTE, LOCALITY, OTHER, ...).
     * @param  bool  $complete  Google `addressComplete`.
     * @param  array<string, mixed>  $raw  The Address Validation `result` payload.
     */
    public function __construct(
        bool $valid,
        ?string $formattedAddress,
        public string $granularity,
        public bool $complete,
        public bool $hasUnconfirmedComponents,
        public bool $hasInferredComponents,
        public bool $hasReplacedComponents,
        array $raw = [],
    ) {
        parent::__construct($valid, 'google', $formattedAddress, $raw);
    }
}
