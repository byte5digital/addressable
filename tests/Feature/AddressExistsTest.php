<?php

use Byte5\Addressable\App\Exceptions\AddressLookupException;
use Byte5\Addressable\App\Facades\AddressRules;
use Byte5\Addressable\App\Rules\AddressExists;
use Illuminate\Support\Facades\Validator;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

function outageResponse(): MockResponse
{
    return MockResponse::make()->throw(
        fn ($pending) => new FatalRequestException(new Exception('provider down'), $pending),
    );
}

beforeEach(function () {
    config()->set('byte5-addressable.validation.providers.google.key', 'test-key');
});

function deliverableResponse(): MockResponse
{
    return MockResponse::make([
        'result' => ['verdict' => ['validationGranularity' => 'PREMISE', 'addressComplete' => true]],
    ]);
}

it('passes when the address validates as deliverable', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => deliverableResponse()]);

    $validator = Validator::make(
        ['street' => 'Pariser Platz 1', 'postal' => '10117', 'city' => 'Berlin', 'country' => 'DE'],
        ['street' => [new AddressExists]],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails when the address is not deliverable', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => MockResponse::make([
        'result' => ['verdict' => ['validationGranularity' => 'OTHER', 'addressComplete' => false]],
    ])]);

    $validator = Validator::make(
        ['street' => 'Nowhere 999', 'postal' => '00000', 'city' => 'Nope', 'country' => 'DE'],
        ['street' => [new AddressExists]],
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('street'))->not->toBeEmpty();
});

it('skips the API call when no address fields are present', function () {
    $mock = MockClient::global(['*' => MockResponse::make([])]);

    $validator = Validator::make(
        ['marker' => 'present'],
        ['marker' => [new AddressExists]],
    );

    expect($validator->passes())->toBeTrue();
    $mock->assertNothingSent();
});

it('reads address fields through a custom map', function () {
    $mock = MockClient::global(['addressvalidation.googleapis.com/*' => deliverableResponse()]);

    $validator = Validator::make(
        ['line1' => 'Pariser Platz 1', 'zip' => '10117', 'town' => 'Berlin', 'cc' => 'DE'],
        ['line1' => [new AddressExists(['street' => 'line1', 'postal' => 'zip', 'city' => 'town', 'country' => 'cc'])]],
    );

    expect($validator->passes())->toBeTrue();

    $mock->assertSent(fn ($request, $response) => in_array(
        'Pariser Platz 1',
        $response->getPendingRequest()->body()->all()['address']['addressLines'],
        true,
    ));
});

it('lets a provider outage bubble by default', function () {
    MockClient::global(['addressvalidation.googleapis.com/*' => outageResponse()]);

    $validator = Validator::make(
        ['street' => 'Pariser Platz 1', 'country' => 'DE'],
        ['street' => [new AddressExists]],
    );

    expect(fn () => $validator->passes())->toThrow(AddressLookupException::class);
});

it('passes on a provider outage when pass_on_outage is enabled', function () {
    config()->set('byte5-addressable.validation.pass_on_outage', true);
    MockClient::global(['addressvalidation.googleapis.com/*' => outageResponse()]);

    $validator = Validator::make(
        ['street' => 'Pariser Platz 1', 'country' => 'DE'],
        ['street' => [new AddressExists]],
    );

    expect($validator->passes())->toBeTrue();
});

it('builds an AddressExists rule through the facade', function () {
    expect(AddressRules::exists())->toBeInstanceOf(AddressExists::class);
});
