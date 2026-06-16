<?php

use Byte5\Addressable\App\Facades\AddressRules;
use Byte5\Addressable\App\Rules\Country;
use Byte5\Addressable\App\Rules\PostalFormat;
use Illuminate\Translation\PotentiallyTranslatedString;

it('builds a PostalFormat rule through the facade', function() {
    expect(AddressRules::postalFormat('DE'))->toBeInstanceOf(PostalFormat::class);
});

it('builds a Country rule through the facade', function() {
    expect(AddressRules::country())->toBeInstanceOf(Country::class);
});

it('the facade-built rule validates the same as a new instance', function() {
    $collect = function($rule) {
        $failures = [];
        $rule->validate('postal', '123', function(string $message) use (&$failures) {
            $failures[] = $string = new PotentiallyTranslatedString($message, app('translator'));

            return $string;
        });

        return array_map('strval', $failures);
    };

    expect($collect(AddressRules::postalFormat('DE')))
        ->toEqual($collect(new PostalFormat('DE')))
        ->not->toBeEmpty();
});
