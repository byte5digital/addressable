<?php

use Byte5\Addressable\App\Contracts\AddressLookup;
use Byte5\Addressable\App\Contracts\LookupFactory;
use Byte5\Addressable\App\Services\AddressLookupManager;
use Byte5\Addressable\App\Services\Drivers\GoogleLookupDriver;

it('resolves the google driver by default', function () {
    expect(app(AddressLookupManager::class)->driver())->toBeInstanceOf(GoogleLookupDriver::class);
});

it('binds the LookupFactory contract to the manager', function () {
    expect(app(LookupFactory::class))->toBeInstanceOf(AddressLookupManager::class);
});

it('binds the AddressLookup contract to the default driver', function () {
    expect(app(AddressLookup::class))->toBeInstanceOf(GoogleLookupDriver::class);
});

it('throws for an unknown provider', function () {
    config()->set('byte5-addressable.lookup.provider', 'nope');

    app(AddressLookupManager::class)->driver();
})->throws(InvalidArgumentException::class);
