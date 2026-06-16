<?php

namespace Byte5\Addressable\App\Events;

use Byte5\Addressable\App\Data\PlaceDetails;

class AddressResolved
{
    /**
     * Dispatched when a `details()` lookup successfully resolves a place into a
     * structured address.
     *
     * @param  string  $provider  The lookup driver, e.g. `google`.
     */
    public function __construct(
        public string $provider,
        public string $placeId,
        public PlaceDetails $details,
    ) {}
}
