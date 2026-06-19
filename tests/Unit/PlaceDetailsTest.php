<?php

use Byte5\Addressable\App\Data\PlaceDetails;

it('maps place details to the Address columns', function () {
    $details = new PlaceDetails(
        street: 'Pariser Platz 1',
        postal: '10117',
        city: 'Berlin',
        region: 'Berlin',
        country: 'DE',
        latitude: 52.5163,
        longitude: 13.3777,
    );

    expect($details->toArray())->toBe([
        'street' => 'Pariser Platz 1',
        'extra' => null,
        'postal' => '10117',
        'city' => 'Berlin',
        'region' => 'Berlin',
        'country' => 'DE',
        'latitude' => 52.5163,
        'longitude' => 13.3777,
    ]);
});

it('maps the extension line to the Address columns', function () {
    $details = new PlaceDetails(
        street: 'Pariser Platz 1',
        extra: 'Apt. 5',
    );

    expect($details->toArray())->toMatchArray([
        'street' => 'Pariser Platz 1',
        'extra' => 'Apt. 5',
    ]);
});

it('defaults every field to null', function () {
    $details = new PlaceDetails;

    expect($details->toArray())->toBe([
        'street' => null,
        'extra' => null,
        'postal' => null,
        'city' => null,
        'region' => null,
        'country' => null,
        'latitude' => null,
        'longitude' => null,
    ]);
});
