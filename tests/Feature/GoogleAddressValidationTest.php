<?php

use Byte5\Addressable\App\Contracts\ValidatesAddresses;
use Byte5\Addressable\App\Data\AddressInput;
use Byte5\Addressable\App\Data\AddressValidation;
use Byte5\Addressable\App\Data\GoogleAddressValidation;
use Byte5\Addressable\App\Events\AddressValidationRequested;
use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Byte5\Addressable\App\Facades\AddressValidator;
use Byte5\Addressable\App\Services\Drivers\GoogleValidationDriver;
use Illuminate\Support\Facades\Event;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function validationDriver(array $config = []): GoogleValidationDriver
{
    return new GoogleValidationDriver(array_merge(['key' => 'test-key'], $config));
}

function sampleAddress(): AddressInput
{
    return new AddressInput(street: 'Pariser Platz 1', postal: '10117', city: 'Berlin', country: 'DE');
}

it('validates an existing address as valid', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make([
        'result' => [
            'verdict' => [
                'inputGranularity' => 'PREMISE',
                'validationGranularity' => 'PREMISE',
                'geocodeGranularity' => 'PREMISE',
                'addressComplete' => true,
                'hasInferredComponents' => true,
            ],
            'address' => ['formattedAddress' => 'Pariser Platz 1, 10117 Berlin, Germany'],
        ],
    ])]);

    $result = validationDriver()->validate(sampleAddress());

    expect($result)->toBeInstanceOf(GoogleAddressValidation::class)
        ->and($result)->toBeInstanceOf(AddressValidation::class)
        ->and($result->valid)->toBeTrue()
        ->and($result->provider)->toBe('google')
        ->and($result->formattedAddress)->toBe('Pariser Platz 1, 10117 Berlin, Germany')
        ->and($result->granularity)->toBe('PREMISE')
        ->and($result->complete)->toBeTrue()
        ->and($result->hasInferredComponents)->toBeTrue()
        ->and($result->hasUnconfirmedComponents)->toBeFalse()
        ->and($result->raw['verdict']['validationGranularity'])->toBe('PREMISE');
});

it('marks a too-coarse address as invalid', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make([
        'result' => ['verdict' => ['validationGranularity' => 'OTHER', 'addressComplete' => false]],
    ])]);

    expect(validationDriver()->validate(sampleAddress())->valid)->toBeFalse();
});

it('marks an address with unconfirmed components as invalid', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make([
        'result' => ['verdict' => [
            'validationGranularity' => 'PREMISE',
            'addressComplete' => true,
            'hasUnconfirmedComponents' => true,
        ]],
    ])]);

    expect(validationDriver()->validate(sampleAddress())->valid)->toBeFalse();
});

it('sends regionCode and address lines built from the structured input', function () {
    $mock = MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make(['result' => ['verdict' => []]])]);

    validationDriver()->validate(sampleAddress());

    $mock->assertSent(function ($request, $response) {
        $pending = $response->getPendingRequest();
        $body = $pending->body()->all();

        return $pending->getUrl() === 'https://addressvalidation.googleapis.com/v1:validateAddress'
            && $pending->headers()->get('X-Goog-Api-Key') === 'test-key'
            && $body['address']['regionCode'] === 'DE'
            && in_array('Pariser Platz 1', $body['address']['addressLines'], true)
            && in_array('10117 Berlin', $body['address']['addressLines'], true);
    });
});

it('throws when the api key is missing', function () {
    MockClient::global(['*' => MockResponse::make([])]);

    validationDriver(['key' => null])->validate(sampleAddress());
})->throws(AddressLookupException::class);

it('throws when the validation request fails', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make('boom', 500)]);

    validationDriver()->validate(sampleAddress());
})->throws(AddressLookupException::class);

it('exposes validation as a capability interface', function () {
    expect(validationDriver())->toBeInstanceOf(ValidatesAddresses::class);
});

it('dispatches AddressValidationRequested for a validate call', function () {
    Event::fake();
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make([
        'result' => ['verdict' => ['validationGranularity' => 'PREMISE', 'addressComplete' => true]],
    ])]);

    $address = sampleAddress();
    validationDriver()->validate($address);

    Event::assertDispatched(AddressValidationRequested::class, fn (AddressValidationRequested $e) => $e->provider === 'google' && $e->valid === true && $e->address === $address);
});

it('validates through the facade', function () {
    config()->set('byte5-addressable.validation.providers.google.key', 'test-key');
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make([
        'result' => ['verdict' => ['validationGranularity' => 'PREMISE', 'addressComplete' => true]],
    ])]);

    expect(AddressValidator::validate(sampleAddress())->valid)->toBeTrue();
});
