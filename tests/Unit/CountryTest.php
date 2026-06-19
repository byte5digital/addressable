<?php

use Byte5\Addressable\App\Rules\Country;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Run the rule and return the collected failure messages.
 *
 * The closure mirrors the framework's own `$fail`, which hands the rule a
 * PotentiallyTranslatedString so `$fail(...)->translate(...)` resolves keys.
 *
 * @return array<int, string>
 */
function countryFailures(mixed $value): array
{
    $failures = [];

    (new Country)->validate(
        'country',
        $value,
        function (string $message) use (&$failures) {
            $failures[] = $string = new PotentiallyTranslatedString($message, app('translator'));

            return $string;
        },
    );

    return array_map('strval', $failures);
}

it('passes a valid ISO 3166-1 alpha-2 country code', function () {
    expect(countryFailures('US'))->toBeEmpty()
        ->and(countryFailures('DE'))->toBeEmpty()
        ->and(countryFailures('NL'))->toBeEmpty();
});

it('is case-insensitive about the country code', function () {
    expect(countryFailures('de'))->toBeEmpty();
});

it('fails an unknown country code', function () {
    expect(countryFailures('ZZ'))->not->toBeEmpty()
        ->and(countryFailures('XX'))->not->toBeEmpty();
});

it('fails a malformed country code', function () {
    expect(countryFailures('1'))->not->toBeEmpty()
        ->and(countryFailures('U'))->not->toBeEmpty()
        ->and(countryFailures('USA'))->not->toBeEmpty();
});

it('skips empty values so required/nullable can own emptiness', function () {
    expect(countryFailures(''))->toBeEmpty()
        ->and(countryFailures(null))->toBeEmpty();
});
