<?php

namespace Byte5\Addressable\App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use League\ISO3166\Exception\ISO3166Exception;
use League\ISO3166\ISO3166;

class Country implements ValidationRule
{
    private static ?ISO3166 $iso3166 = null;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Leave emptiness (and non-strings) to required/nullable/string rules.
        if (! is_string($value) || $value === '') {
            return;
        }

        try {
            // Normalise to uppercase to match the stored ISO 3166-1 alpha-2 code.
            self::iso3166()->alpha2(strtoupper($value));
        } catch (ISO3166Exception) {
            // Unknown code (OutOfBoundsException) or malformed input (DomainException).
            $fail('byte5-addressable::validation.country')->translate(['attribute' => $attribute]);
        }
    }

    private static function iso3166(): ISO3166
    {
        return self::$iso3166 ??= new ISO3166();
    }
}
