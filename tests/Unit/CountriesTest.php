<?php

use Byte5\Addressable\App\Rules\Country;
use Byte5\Addressable\App\Services\Countries;
use Illuminate\Translation\PotentiallyTranslatedString;

it('lists country codes mapped to names', function() {
    $list = (new Countries())->list();

    expect($list)->toHaveKey('DE', 'Germany')
        ->and($list)->toHaveKey('US', 'United States');
});

it('localises the country names', function() {
    expect((new Countries())->list('de'))->toHaveKey('DE', 'Deutschland');
});

it('preserves the locale-specific ordering', function() {
    $de = array_keys((new Countries())->list('de'));

    // "Ägypten" (EG) sorts before "Albanien" (AL) in German; the reverse in English.
    expect(array_search('EG', $de, true))->toBeLessThan(array_search('AL', $de, true));
});

it('excludes codes that are not official ISO 3166-1 alpha-2', function() {
    $list = (new Countries())->list();

    expect($list)->not->toHaveKey('AC')   // Ascension Island
        ->and($list)->not->toHaveKey('IC') // Canary Islands
        ->and($list)->not->toHaveKey('EA') // Ceuta & Melilla
        ->and($list)->not->toHaveKey('CP') // Clipperton Island
        ->and($list)->not->toHaveKey('DG') // Diego Garcia
        ->and($list)->not->toHaveKey('TA') // Tristan da Cunha
        ->and($list)->toHaveCount(250);
});

it('only lists codes that the Country rule accepts', function() {
    $rule = new Country();

    foreach (array_keys((new Countries())->list()) as $code) {
        $failures = [];
        $rule->validate('country', $code, function(string $message) use (&$failures) {
            $failures[] = $string = new PotentiallyTranslatedString($message, app('translator'));

            return $string;
        });

        expect($failures)->toBeEmpty("country code {$code} should pass the Country rule");
    }
});
