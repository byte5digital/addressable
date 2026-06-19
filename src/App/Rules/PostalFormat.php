<?php

namespace Byte5\Addressable\App\Rules;

use Closure;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use Illuminate\Contracts\Validation\ValidationRule;

class PostalFormat implements ValidationRule
{
    private static ?AddressFormatRepository $repository = null;

    public function __construct(protected ?string $country) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Leave emptiness (and non-strings) to required/nullable/string rules, and skip when there is no country to resolve a format from.
        if ($this->country === null || ! is_string($value) || $value === '') {
            return;
        }

        $pattern = self::repository()->get($this->country)->getPostalCodePattern();

        // Countries without postal codes (or unknown countries) have no pattern to check against.
        if ($pattern === null) {
            return;
        }

        // Mirror commerceguys/addressing: the pattern must match the value completely.
        preg_match('/'.$pattern.'/i', $value, $matches);

        if (! isset($matches[0]) || $matches[0] !== $value) {
            $fail('byte5-addressable::validation.postal_format')->translate(['attribute' => $attribute]);
        }
    }

    private static function repository(): AddressFormatRepository
    {
        return self::$repository ??= new AddressFormatRepository;
    }
}
