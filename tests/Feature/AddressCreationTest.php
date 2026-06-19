<?php

use Byte5\Addressable\App\Contracts\Addressable;
use Byte5\Addressable\App\Contracts\CreatesAddresses;
use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Enums\AddressType;
use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\Tests\Fixtures\TestModel;
use Illuminate\Database\Eloquent\Model;

it('addAddress with AddressData persists and returns an Address belonging to the owner', function () {
    $model = TestModel::create(['name' => 'Acme']);

    $data = new AddressData(
        type: AddressType::Billing,
        street: 'Main 10',
        postal: '10115',
        city: 'Berlin',
        country: 'DE',
    );

    $address = $model->addAddress($data);

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->exists)->toBeTrue()
        ->and($address->street)->toBe('Main 10')
        ->and($address->city)->toBe('Berlin')
        ->and($address->postal)->toBe('10115')
        ->and($address->country)->toBe('DE')
        ->and($model->addresses()->count())->toBe(1)
        ->and($model->addresses()->first()->is($address))->toBeTrue();
});

it('addAddress with a loose array persists via fromArray', function () {
    $model = TestModel::create(['name' => 'Acme']);

    $address = $model->addAddress([
        'street' => 'Unter den Linden 1',
        'city' => 'Berlin',
        'postal' => '10117',
        'country' => 'DE',
    ]);

    expect($address)->toBeInstanceOf(Address::class)
        ->and($address->exists)->toBeTrue()
        ->and($address->street)->toBe('Unter den Linden 1')
        ->and($address->city)->toBe('Berlin')
        ->and($address->country)->toBe('DE')
        ->and($model->addresses()->count())->toBe(1);
});

it('addAddress with an explicit $type overrides and uppercases country', function () {
    $model = TestModel::create(['name' => 'Acme']);

    $data = new AddressData(
        type: AddressType::Shipping,
        street: 'Marienplatz 1',
        city: 'Munich',
        country: 'de',
    );

    $address = $model->addAddress($data, AddressType::Billing);

    $fresh = $address->fresh();

    expect($fresh)->not->toBeNull()
        ->and($fresh->country)->toBe('DE')
        ->and($fresh->type)->toBe(AddressType::Billing);
});

it('addAddress persists a plain-string type when the enum cast is disabled', function () {
    config()->set('byte5-addressable.type_enum', '');

    $model = TestModel::create(['name' => 'Acme']);

    $data = new AddressData(type: 'custom', city: 'Berlin');

    $address = $model->addAddress($data);

    expect($address->fresh()->type)->toBe('custom');
});

it('addAddress routes through the container so the contract can be rebound', function () {
    $model = TestModel::create(['name' => 'Acme']);

    $sentinel = $model->addresses()->create(['city' => 'Sentinel']);
    $spy = (object) ['called' => false];

    app()->bind(CreatesAddresses::class, function () use ($sentinel, $spy): CreatesAddresses {
        return new class($sentinel, $spy) implements CreatesAddresses
        {
            public function __construct(
                private readonly Address $sentinel,
                private readonly object $spy,
            ) {}

            public function create(Model&Addressable $owner, AddressData $data): Address
            {
                $this->spy->called = true;

                return $this->sentinel;
            }
        };
    });

    $result = $model->addAddress(new AddressData(city: 'Berlin'));

    expect($spy->called)->toBeTrue()
        ->and($result->is($sentinel))->toBeTrue();
});
