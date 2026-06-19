<?php

use Byte5\Addressable\App\Data\Suggestion;
use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Byte5\Addressable\App\Services\Drivers\GoogleLookupDriver;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function googleDriver(array $config = []): GoogleLookupDriver
{
    return new GoogleLookupDriver(array_merge(['key' => 'test-key'], $config));
}

it('returns suggestions for a query', function () {
    MockClient::global([
        'places.googleapis.com/v1/places:autocomplete' => MockResponse::make([
            'suggestions' => [
                [
                    'placePrediction' => [
                        'placeId' => 'ChIJ123',
                        'text' => ['text' => 'Brandenburger Tor, Berlin, Germany'],
                        'structuredFormat' => [
                            'mainText' => ['text' => 'Brandenburger Tor'],
                            'secondaryText' => ['text' => 'Berlin, Germany'],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    $suggestions = googleDriver()->suggest('Brandenburger');

    expect($suggestions)->toHaveCount(1)
        ->and($suggestions[0])->toBeInstanceOf(Suggestion::class)
        ->and($suggestions[0]->placeId)->toBe('ChIJ123')
        ->and($suggestions[0]->mainText)->toBe('Brandenburger Tor')
        ->and($suggestions[0]->secondaryText)->toBe('Berlin, Germany')
        ->and($suggestions[0]->description)->toBe('Brandenburger Tor, Berlin, Germany');
});

it('sends the api key header and request body', function () {
    $mock = MockClient::global(['places.googleapis.com/*' => MockResponse::make(['suggestions' => []])]);

    googleDriver(['country' => 'DE', 'language' => 'de'])->suggest('Haupt');

    $mock->assertSent(function ($request, $response) {
        $pending = $response->getPendingRequest();
        $body = $pending->body()->all();

        return $pending->getUrl() === 'https://places.googleapis.com/v1/places:autocomplete'
            && $pending->headers()->get('X-Goog-Api-Key') === 'test-key'
            && $body['input'] === 'Haupt'
            && $body['languageCode'] === 'de'
            && $body['includedRegionCodes'] === ['DE'];
    });
});

it('returns an empty array and makes no request for a blank query', function () {
    $mock = MockClient::global(['*' => MockResponse::make([])]);

    expect(googleDriver()->suggest('   '))->toBe([]);

    $mock->assertNothingSent();
});

it('skips query predictions that have no place', function () {
    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make([
            'suggestions' => [['queryPrediction' => ['text' => ['text' => 'pizza near me']]]],
        ]),
    ]);

    expect(googleDriver()->suggest('pizza'))->toBe([]);
});

it('throws when the api key is missing', function () {
    MockClient::global(['*' => MockResponse::make([])]);

    googleDriver(['key' => null])->suggest('Berlin');
})->throws(AddressLookupException::class);

it('throws when the autocomplete request fails', function () {
    MockClient::global(['places.googleapis.com/*' => MockResponse::make('nope', 500)]);

    googleDriver()->suggest('Berlin');
})->throws(AddressLookupException::class);

it('skips predictions that have no place id', function () {
    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make([
            'suggestions' => [
                ['placePrediction' => ['text' => ['text' => 'No id here']]],
            ],
        ]),
    ]);

    expect(googleDriver()->suggest('x'))->toBe([]);
});

it('resolves a place id into structured address fields', function () {
    MockClient::global([
        'places.googleapis.com/v1/places/*' => MockResponse::make([
            'id' => 'ChIJ123',
            'formattedAddress' => 'Pariser Platz 1, 10117 Berlin, Germany',
            'addressComponents' => [
                ['longText' => 'Pariser Platz', 'shortText' => 'Pariser Platz', 'types' => ['route']],
                ['longText' => '1', 'shortText' => '1', 'types' => ['street_number']],
                ['longText' => '10117', 'shortText' => '10117', 'types' => ['postal_code']],
                ['longText' => 'Berlin', 'shortText' => 'Berlin', 'types' => ['locality', 'political']],
                ['longText' => 'Berlin', 'shortText' => 'BE', 'types' => ['administrative_area_level_1', 'political']],
                ['longText' => 'Germany', 'shortText' => 'DE', 'types' => ['country', 'political']],
            ],
            'location' => ['latitude' => 52.5163, 'longitude' => 13.3777],
        ]),
    ]);

    $details = googleDriver()->details('ChIJ123');

    expect($details->street)->toBe('Pariser Platz 1')
        ->and($details->postal)->toBe('10117')
        ->and($details->city)->toBe('Berlin')
        ->and($details->region)->toBe('Berlin')
        ->and($details->country)->toBe('DE')
        ->and($details->latitude)->toBe(52.5163)
        ->and($details->longitude)->toBe(13.3777);
});

it('maps the subpremise component to the extra line', function () {
    MockClient::global([
        'places.googleapis.com/v1/places/*' => MockResponse::make([
            'formattedAddress' => 'Pariser Platz 1, 10117 Berlin, Germany',
            'addressComponents' => [
                ['longText' => '5', 'shortText' => '5', 'types' => ['subpremise']],
            ],
        ]),
    ]);

    expect(googleDriver()->details('ChIJ123')->extra)->toBe('5');
});

it('sends the field mask and api key for details', function () {
    $mock = MockClient::global(['places.googleapis.com/*' => MockResponse::make(['id' => 'ChIJ123'])]);

    googleDriver()->details('ChIJ123');

    $mock->assertSent(function ($request, $response) {
        $pending = $response->getPendingRequest();

        return str_starts_with($pending->getUrl(), 'https://places.googleapis.com/v1/places/ChIJ123')
            && $pending->headers()->get('X-Goog-Api-Key') === 'test-key'
            && $pending->headers()->get('X-Goog-FieldMask') === 'id,formattedAddress,addressComponents,location';
    });
});

it('returns null when the place is not found', function () {
    MockClient::global(['places.googleapis.com/*' => MockResponse::make(['error' => 'not found'], 404)]);

    expect(googleDriver()->details('missing'))->toBeNull();
});

it('throws when the details request fails', function () {
    MockClient::global(['places.googleapis.com/*' => MockResponse::make('boom', 500)]);

    googleDriver()->details('ChIJ123');
})->throws(AddressLookupException::class);

it('falls back through city component types', function () {
    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make([
            'addressComponents' => [
                ['longText' => 'Westminster', 'shortText' => 'Westminster', 'types' => ['postal_town']],
            ],
        ]),
    ]);

    expect(googleDriver()->details('ChIJ123')->city)->toBe('Westminster');
});

it('wraps a connection failure when suggesting', function () {
    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make()->throw(
            fn ($pending) => new FatalRequestException(new Exception('boom'), $pending),
        ),
    ]);

    googleDriver()->suggest('Berlin');
})->throws(AddressLookupException::class);

it('wraps a connection failure when fetching details', function () {
    MockClient::global([
        'places.googleapis.com/*' => MockResponse::make()->throw(
            fn ($pending) => new FatalRequestException(new Exception('boom'), $pending),
        ),
    ]);

    googleDriver()->details('ChIJ123');
})->throws(AddressLookupException::class);

it('falls back to the app locale for the language when none is configured', function () {
    app()->setLocale('de');

    $mock = MockClient::global(['places.googleapis.com/*' => MockResponse::make(['suggestions' => []])]);

    googleDriver()->suggest('Haupt');

    $mock->assertSent(fn ($request, $response) => $response->getPendingRequest()->body()->all()['languageCode'] === 'de');
});

it('falls back to the app locale for the language when fetching details', function () {
    app()->setLocale('de');

    $mock = MockClient::global(['places.googleapis.com/*' => MockResponse::make(['id' => 'ChIJ123'])]);

    googleDriver()->details('ChIJ123');

    $mock->assertSent(fn ($request, $response) => $response->getPendingRequest()->query()->all()['languageCode'] === 'de');
});

it('lets a per-call language override the app locale', function () {
    app()->setLocale('de');

    $mock = MockClient::global(['places.googleapis.com/*' => MockResponse::make(['suggestions' => []])]);

    googleDriver()->suggest('Haupt', ['language' => 'fr']);

    $mock->assertSent(fn ($request, $response) => $response->getPendingRequest()->body()->all()['languageCode'] === 'fr');
});

it('treats a blank configured language as unset and falls back to the app locale', function () {
    app()->setLocale('de');

    $mock = MockClient::global(['places.googleapis.com/*' => MockResponse::make(['suggestions' => []])]);

    googleDriver(['language' => ''])->suggest('Haupt');

    $mock->assertSent(fn ($request, $response) => $response->getPendingRequest()->body()->all()['languageCode'] === 'de');
});
