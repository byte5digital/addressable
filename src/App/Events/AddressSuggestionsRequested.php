<?php

namespace Byte5\Addressable\App\Events;

class AddressSuggestionsRequested
{
    /**
     * Dispatched after a suggest() request completes (not on failure).
     *
     * @param  string  $provider  The lookup driver, e.g. `google`.
     * @param  string  $query  The search string.
     * @param  array<string, mixed>  $options  Per-call options passed to the driver.
     * @param  int  $count  Number of suggestions returned.
     */
    public function __construct(
        public string $provider,
        public string $query,
        public array $options,
        public int $count,
    ) {}
}
