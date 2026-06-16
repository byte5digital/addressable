<?php

use Byte5\Addressable\App\Contracts\ValidatesAddresses;
use Byte5\Addressable\App\Contracts\ValidationFactory;
use Byte5\Addressable\App\Services\AddressValidationManager;
use Byte5\Addressable\App\Services\Drivers\GoogleValidationDriver;

it('resolves the google validation driver by default', function () {
    expect(app(AddressValidationManager::class)->driver())->toBeInstanceOf(GoogleValidationDriver::class);
});

it('binds the ValidationFactory contract to the manager', function () {
    expect(app(ValidationFactory::class))->toBeInstanceOf(AddressValidationManager::class);
});

it('binds the ValidatesAddresses contract to the default validation driver', function () {
    expect(app(ValidatesAddresses::class))->toBeInstanceOf(GoogleValidationDriver::class);
});

it('reads the configured validation provider', function () {
    config()->set('byte5-addressable.validation.provider', 'google');

    expect(app(AddressValidationManager::class)->getDefaultDriver())->toBe('google');
});

it('throws for an unknown validation provider', function () {
    config()->set('byte5-addressable.validation.provider', 'nope');

    app(AddressValidationManager::class)->driver();
})->throws(InvalidArgumentException::class);
