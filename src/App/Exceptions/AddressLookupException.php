<?php

namespace Byte5\Addressable\App\Exceptions;

use RuntimeException;

class AddressLookupException extends RuntimeException
{
    public static function missingApiKey(string $envVar = 'ADDRESSABLE_LOOKUP_GOOGLE_KEY'): self
    {
        return new self("Missing Google API key. Set the {$envVar} env var.");
    }

    public static function requestFailed(string $provider, int $status, string $body): self
    {
        return new self("The [{$provider}] address lookup request failed with status {$status}: {$body}");
    }

    public static function connectionFailed(string $provider, \Throwable $previous): self
    {
        return new self("Could not reach the [{$provider}] address lookup provider: {$previous->getMessage()}", 0, $previous);
    }
}
