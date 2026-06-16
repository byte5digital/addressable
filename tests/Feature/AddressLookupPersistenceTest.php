<?php

use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\App\Facades\AddressLookup;
use Byte5\Addressable\Tests\Fixtures\TestModel;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('persists resolved place details onto an address', function () {
    config()->set('byte5-addressable.lookup.providers.google.key', 'test-key');

    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make([
            'formattedAddress' => 'Pariser Platz 1, 10117 Berlin, Germany',
            'addressComponents' => [
                ['longText' => 'Pariser Platz', 'shortText' => 'Pariser Platz', 'types' => ['route']],
                ['longText' => '1', 'shortText' => '1', 'types' => ['street_number']],
                ['longText' => '10117', 'shortText' => '10117', 'types' => ['postal_code']],
                ['longText' => 'Berlin', 'shortText' => 'Berlin', 'types' => ['locality']],
                ['longText' => 'Berlin', 'shortText' => 'BE', 'types' => ['administrative_area_level_1']],
                ['longText' => 'Germany', 'shortText' => 'DE', 'types' => ['country']],
            ],
            'location' => ['latitude' => 52.5163, 'longitude' => 13.3777],
        ]),
    ]);

    $model = TestModel::create(['name' => 'Acme']);

    $details = AddressLookup::details('ChIJ123');
    $address = $model->addresses()->create($details->toArray());

    $fresh = $address->fresh();

    expect($fresh)->toBeInstanceOf(Address::class)
        ->and($fresh->street)->toBe('Pariser Platz 1')
        ->and($fresh->postal)->toBe('10117')
        ->and($fresh->city)->toBe('Berlin')
        ->and($fresh->region)->toBe('Berlin')
        ->and($fresh->country)->toBe('DE')
        ->and($fresh->latitude)->toBe('52.51630000')
        ->and($fresh->longitude)->toBe('13.37770000');
});
