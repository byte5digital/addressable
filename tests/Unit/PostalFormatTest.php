<?php

use Byte5\Addressable\App\Rules\PostalFormat;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Run the rule and return the collected failure messages.
 *
 * The closure mirrors the framework's own `$fail`, which hands the rule a
 * PotentiallyTranslatedString so `$fail(...)->translate(...)` resolves keys.
 *
 * @return array<int, string>
 */
function postalFailures(?string $country, mixed $value): array
{
    $failures = [];

    (new PostalFormat($country))->validate(
        'postal',
        $value,
        function(string $message) use (&$failures) {
            $failures[] = $string = new PotentiallyTranslatedString($message, app('translator'));

            return $string;
        },
    );

    return array_map('strval', $failures);
}

it('passes a valid postal code for the country', function() {
    expect(postalFailures('DE', '10115'))->toBeEmpty()
        ->and(postalFailures('AT', '1010'))->toBeEmpty()
        ->and(postalFailures('NL', '1011 AB'))->toBeEmpty();
});

it('fails an invalid postal code for the country', function() {
    expect(postalFailures('DE', '123'))->not->toBeEmpty()
        ->and(postalFailures('DE', 'ABCDE'))->not->toBeEmpty()
        ->and(postalFailures('AT', '10115'))->not->toBeEmpty();
});

it('is case-insensitive about the country code', function() {
    expect(postalFailures('de', '10115'))->toBeEmpty();
});

it('does not validate countries without a known pattern', function() {
    expect(postalFailures('ZZ', 'anything-goes'))->toBeEmpty()
        ->and(postalFailures(null, 'anything-goes'))->toBeEmpty();
});

it('skips empty values so required/nullable can own emptiness', function() {
    expect(postalFailures('DE', ''))->toBeEmpty()
        ->and(postalFailures('DE', null))->toBeEmpty();
});

it('validates countries via commerceguys/addressing data', function() {
    expect(postalFailures('CA', 'K1A 0B1'))->toBeEmpty()
        ->and(postalFailures('CA', '12345'))->not->toBeEmpty();
});

it('does not validate countries that have no postal codes', function() {
    expect(postalFailures('AE', 'whatever'))->toBeEmpty()
        ->and(postalFailures('HK', 'whatever'))->toBeEmpty();
});
