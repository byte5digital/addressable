<?php

use Byte5\Addressable\App\Facades\AddressLookup;
use Byte5\Addressable\App\Services\Drivers\GoogleLookupDriver;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config()->set('byte5-addressable.lookup.providers.google.key', 'test-key');
});

it('suggests addresses through the facade', function () {
    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make([
            'suggestions' => [
                ['placePrediction' => [
                    'placeId' => 'ChIJ123',
                    'text' => ['text' => 'Berlin, Germany'],
                    'structuredFormat' => [
                        'mainText' => ['text' => 'Berlin'],
                        'secondaryText' => ['text' => 'Germany'],
                    ],
                ]],
            ],
        ]),
    ]);

    $suggestions = AddressLookup::suggest('Berlin');

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0]->placeId)->toBe('ChIJ123');
});

it('exposes the google driver explicitly', function () {
    expect(AddressLookup::driver('google'))->toBeInstanceOf(GoogleLookupDriver::class);
});
