<?php

use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Data\PlaceDetails;
use Byte5\Addressable\App\Data\PostalAddress;
use Byte5\Addressable\App\Enums\AddressType;

it('fromArray() populates all known fields', function () {
    $data = AddressData::fromArray([
        'type'      => AddressType::Billing,
        'street'    => 'Pariser Platz 1',
        'extra'     => 'Apt. 5',
        'postal'    => '10117',
        'city'      => 'Berlin',
        'region'    => 'Berlin',
        'latitude'  => 52.5163,
        'longitude' => 13.3777,
        'country'   => 'DE',
    ]);

    expect($data->type)->toBe(AddressType::Billing)
        ->and($data->street)->toBe('Pariser Platz 1')
        ->and($data->extra)->toBe('Apt. 5')
        ->and($data->postal)->toBe('10117')
        ->and($data->city)->toBe('Berlin')
        ->and($data->region)->toBe('Berlin')
        ->and($data->latitude)->toBe(52.5163)
        ->and($data->longitude)->toBe(13.3777)
        ->and($data->country)->toBe('DE');
});

it('fromArray() ignores unknown keys', function () {
    $data = AddressData::fromArray([
        'city'    => 'Berlin',
        'unknown' => 'ignored',
        'extra_field' => 'also ignored',
    ]);

    expect($data->city)->toBe('Berlin')
        ->and($data->street)->toBeNull();
});

it('toArray() returns all nine column keys', function () {
    $data = new AddressData(
        type: AddressType::Shipping,
        street: 'Unter den Linden 1',
        extra: null,
        postal: '10117',
        city: 'Berlin',
        region: 'Berlin',
        latitude: 52.5163,
        longitude: 13.3777,
        country: 'DE',
    );

    expect($data->toArray())->toHaveCount(9)->toHaveKeys([
        'type', 'street', 'extra', 'postal', 'city', 'region', 'latitude', 'longitude', 'country',
    ]);
});

it('toArray() normalises AddressType enum to its backing value', function () {
    $data = new AddressData(type: AddressType::Billing);

    expect($data->toArray()['type'])->toBe('billing');
});

it('toArray() passes a plain string type through unchanged', function () {
    $data = new AddressData(type: 'custom');

    expect($data->toArray()['type'])->toBe('custom');
});

it('toArray() keeps null type as null', function () {
    $data = new AddressData();

    expect($data->toArray()['type'])->toBeNull();
});

it('withType() returns a new instance with the type set and all other fields preserved', function () {
    $original = new AddressData(
        type: null,
        street: 'Pariser Platz 1',
        extra: 'Apt. 5',
        postal: '10117',
        city: 'Berlin',
        region: 'Berlin',
        latitude: 52.5163,
        longitude: 13.3777,
        country: 'DE',
    );

    $updated = $original->withType(AddressType::Home);

    expect($updated)->not->toBe($original)
        ->and($updated->type)->toBe(AddressType::Home)
        ->and($updated->street)->toBe('Pariser Platz 1')
        ->and($updated->extra)->toBe('Apt. 5')
        ->and($updated->postal)->toBe('10117')
        ->and($updated->city)->toBe('Berlin')
        ->and($updated->region)->toBe('Berlin')
        ->and($updated->latitude)->toBe(52.5163)
        ->and($updated->longitude)->toBe(13.3777)
        ->and($updated->country)->toBe('DE');
});

it('withType() leaves the original instance unchanged', function () {
    $original = new AddressData(type: null, city: 'Berlin');
    $original->withType(AddressType::Work);

    expect($original->type)->toBeNull();
});

it('PlaceDetails::toAddressData() maps every field including latitude and longitude', function () {
    $place = new PlaceDetails(
        street: 'Pariser Platz 1',
        extra: 'Apt. 5',
        postal: '10117',
        city: 'Berlin',
        region: 'Berlin',
        country: 'DE',
        latitude: 52.5163,
        longitude: 13.3777,
    );

    $data = $place->toAddressData();

    expect($data->type)->toBeNull()
        ->and($data->street)->toBe('Pariser Platz 1')
        ->and($data->extra)->toBe('Apt. 5')
        ->and($data->postal)->toBe('10117')
        ->and($data->city)->toBe('Berlin')
        ->and($data->region)->toBe('Berlin')
        ->and($data->latitude)->toBe(52.5163)
        ->and($data->longitude)->toBe(13.3777)
        ->and($data->country)->toBe('DE');
});

it('PlaceDetails::toAddressData() applies the optional type argument', function () {
    $place = new PlaceDetails(city: 'Berlin');

    $data = $place->toAddressData(AddressType::Work);

    expect($data->type)->toBe(AddressType::Work);
});

it('PostalAddress::toAddressData() maps location fields and leaves geo null', function () {
    $postal = new PostalAddress(
        street: 'Pariser Platz 1',
        extra: 'Apt. 5',
        postal: '10117',
        city: 'Berlin',
        region: 'Berlin',
        country: 'DE',
    );

    $data = $postal->toAddressData();

    expect($data->type)->toBeNull()
        ->and($data->street)->toBe('Pariser Platz 1')
        ->and($data->extra)->toBe('Apt. 5')
        ->and($data->postal)->toBe('10117')
        ->and($data->city)->toBe('Berlin')
        ->and($data->region)->toBe('Berlin')
        ->and($data->latitude)->toBeNull()
        ->and($data->longitude)->toBeNull()
        ->and($data->country)->toBe('DE');
});

it('PostalAddress::toAddressData() applies the optional type argument', function () {
    $postal = new PostalAddress(city: 'Berlin');

    $data = $postal->toAddressData(AddressType::Billing);

    expect($data->type)->toBe(AddressType::Billing);
});

it('fromArray() accepts integer coordinates and casts them to float', function () {
    $data = AddressData::fromArray(['latitude' => 48, 'longitude' => 13]);

    expect($data->latitude)->toBe(48.0)
        ->and($data->longitude)->toBe(13.0);
});
