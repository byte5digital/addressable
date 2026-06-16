<?php

use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Byte5\Addressable\App\Support\Config;

it('defaults the lookup provider to google', function () {
    expect(Config::lookupProvider())->toBe('google');
});

it('reads the configured lookup provider', function () {
    config()->set('byte5-addressable.lookup.provider', 'nominatim');

    expect(Config::lookupProvider())->toBe('nominatim');
});

it('returns the google provider config array', function () {
    config()->set('byte5-addressable.lookup.providers.google.key', 'abc');

    expect(Config::googleConfig())->toMatchArray(['key' => 'abc']);
});

it('defaults pass-on-outage to false', function () {
    expect(Config::validationPassesOnOutage())->toBeFalse();
});

it('reads the configured pass-on-outage flag', function () {
    config()->set('byte5-addressable.validation.pass_on_outage', true);

    expect(Config::validationPassesOnOutage())->toBeTrue();
});

it('builds a missing-api-key exception that names the env var', function () {
    expect(AddressLookupException::missingApiKey()->getMessage())
        ->toContain('ADDRESSABLE_LOOKUP_GOOGLE_KEY');
});
