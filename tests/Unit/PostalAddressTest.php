<?php

use Byte5\Addressable\App\Data\PostalAddress;

it('maps fields to schema.org PostalAddress keys', function () {
    $address = new PostalAddress(
        street: 'Pariser Platz 1',
        extra: 'Apt. 5',
        postal: '10117',
        city: 'Berlin',
        region: 'Berlin',
        country: 'DE',
    );

    expect($address->toArray())->toBe([
        '@type' => 'PostalAddress',
        'streetAddress' => 'Pariser Platz 1',
        'extendedAddress' => 'Apt. 5',
        'postalCode' => '10117',
        'addressLocality' => 'Berlin',
        'addressRegion' => 'Berlin',
        'addressCountry' => 'DE',
    ]);
});

it('omits null fields from the array fragment', function () {
    $address = new PostalAddress(
        street: 'Pariser Platz 1',
        city: 'Berlin',
    );

    expect($address->toArray())->toBe([
        '@type' => 'PostalAddress',
        'streetAddress' => 'Pariser Platz 1',
        'addressLocality' => 'Berlin',
    ]);
});

it('never includes @context in the array fragment', function () {
    expect((new PostalAddress(city: 'Berlin'))->toArray())
        ->not->toHaveKey('@context');
});

it('renders standalone JSON-LD with @context first', function () {
    $address = new PostalAddress(
        street: 'Pariser Platz 1',
        postal: '10117',
        city: 'Berlin',
        country: 'DE',
    );

    $decoded = json_decode($address->toJsonLd(), true);

    expect($decoded)->toBe([
        '@context' => 'https://schema.org',
        '@type' => 'PostalAddress',
        'streetAddress' => 'Pariser Platz 1',
        'postalCode' => '10117',
        'addressLocality' => 'Berlin',
        'addressCountry' => 'DE',
    ]);
});
