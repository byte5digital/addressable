<?php

use Byte5\Addressable\App\Events\AddressDetailsRequested;
use Byte5\Addressable\App\Events\AddressResolved;
use Byte5\Addressable\App\Events\AddressSuggestionsRequested;
use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Byte5\Addressable\App\Services\Drivers\GoogleLookupDriver;
use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function eventsDriver(): GoogleLookupDriver
{
    return new GoogleLookupDriver(['key' => 'test-key']);
}

it('dispatches AddressSuggestionsRequested for a suggest call', function () {
    Event::fake();
    MockClient::global(['places.googleapis.com/*' => MockResponse::make([
        'suggestions' => [
            ['placePrediction' => [
                'placeId' => 'ChIJ1',
                'text' => ['text' => 'Berlin, Germany'],
                'structuredFormat' => [
                    'mainText' => ['text' => 'Berlin'],
                    'secondaryText' => ['text' => 'Germany'],
                ],
            ]],
        ],
    ])]);

    eventsDriver()->suggest('Berlin', ['language' => 'de']);

    Event::assertDispatched(AddressSuggestionsRequested::class, fn (AddressSuggestionsRequested $e) => $e->provider === 'google'
        && $e->query === 'Berlin'
        && $e->options === ['language' => 'de']
        && $e->count === 1);
});

it('dispatches AddressDetailsRequested and AddressResolved for a resolved details call', function () {
    Event::fake();
    MockClient::global(['places.googleapis.com/*' => MockResponse::make([
        'id' => 'ChIJ1',
        'addressComponents' => [
            ['longText' => '10117', 'shortText' => '10117', 'types' => ['postal_code']],
            ['longText' => 'Berlin', 'shortText' => 'Berlin', 'types' => ['locality']],
        ],
    ])]);

    eventsDriver()->details('ChIJ1');

    Event::assertDispatched(AddressDetailsRequested::class, fn (AddressDetailsRequested $e) => $e->provider === 'google' && $e->placeId === 'ChIJ1' && $e->found === true);

    Event::assertDispatched(AddressResolved::class, fn (AddressResolved $e) => $e->provider === 'google'
        && $e->placeId === 'ChIJ1'
        && $e->details->postal === '10117'
        && $e->details->city === 'Berlin');
});

it('dispatches AddressDetailsRequested without a resolution when details is not found', function () {
    Event::fake();
    MockClient::global(['places.googleapis.com/*' => MockResponse::make(['error' => 'nope'], 404)]);

    eventsDriver()->details('missing');

    Event::assertDispatched(AddressDetailsRequested::class, fn (AddressDetailsRequested $e) => $e->placeId === 'missing' && $e->found === false);
    Event::assertNotDispatched(AddressResolved::class);
});

it('dispatches no events for a blank suggest query', function () {
    Event::fake();
    MockClient::global(['*' => MockResponse::make([])]);

    eventsDriver()->suggest('   ');

    Event::assertNotDispatched(AddressSuggestionsRequested::class);
});

it('dispatches no events when the provider request fails', function () {
    Event::fake();
    MockClient::global(['places.googleapis.com/*' => MockResponse::make('boom', 500)]);

    try {
        eventsDriver()->suggest('Berlin');
    } catch (AddressLookupException) {
        // expected
    }

    Event::assertNotDispatched(AddressSuggestionsRequested::class);
});
