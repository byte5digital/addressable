<?php

namespace Byte5\Addressable\App\Data;

readonly class Suggestion
{
    public function __construct(
        public string $placeId,
        public string $description,
        public string $mainText,
        public string $secondaryText,
    ) {}
}
