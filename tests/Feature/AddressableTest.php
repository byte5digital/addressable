<?php

use Byte5\Addressable\App\Models\Address;
use Byte5\Addressable\App\Data\PostalAddress;
use Byte5\Addressable\App\Enums\AddressType;
use Byte5\Addressable\Tests\Fixtures\CustomAddress;
use Byte5\Addressable\Tests\Fixtures\CustomAddressType;
use Byte5\Addressable\Tests\Fixtures\TestModel;
use Illuminate\Support\Facades\Schema;

it('attaches and retrieves addresses via the morphMany relation', function() {
    $model = TestModel::create(['name' => 'Acme']);

    $model->addresses()->create([
        'street' => 'Main 10',
        'city' => 'Berlin',
    ]);

    expect($model->addresses)->toHaveCount(1)
        ->and($model->addresses->first())->toBeInstanceOf(Address::class)
        ->and($model->addresses->first()->city)->toBe('Berlin');
});

it('exposes a single address via the morphOne relation', function() {
    $model = TestModel::create(['name' => 'Acme']);

    $model->addresses()->create(['city' => 'Berlin', 'type' => 'billing']);
    $latest = $model->addresses()->create(['city' => 'Munich', 'type' => 'shipping']);

    expect($model->latestAddress)->toBeInstanceOf(Address::class)
        ->and($model->latestAddress->is($latest))->toBeTrue();
});

it('resolves the owning model through the addressable morphTo relation', function() {
    $model = TestModel::create(['name' => 'Acme']);
    $address = $model->addresses()->create(['city' => 'Berlin']);

    expect($address->addressable)->toBeInstanceOf(TestModel::class)
        ->and($address->addressable->is($model))->toBeTrue();
});

it('casts latitude and longitude to 8-decimal precision', function() {
    $address = Address::factory()->create([
        'latitude' => 52.52,
        'longitude' => 13.405,
    ]);

    expect($address->fresh()->latitude)->toBe('52.52000000')
        ->and($address->fresh()->longitude)->toBe('13.40500000');
});

it('uses a big integer addressable key by default', function() {
    expect(Schema::getColumnType('addresses', 'addressable_id'))->toBe('integer');
});

it('uses a configurable table name', function() {
    config()->set('byte5-addressable.table_names.addresses', 'postal_addresses');

    Schema::dropIfExists('addresses');
    $migration = include __DIR__.'/../../database/migrations/create_addresses_table.php.stub';
    $migration->up();

    expect(Schema::hasTable('postal_addresses'))->toBeTrue()
        ->and((new Address())->getTable())->toBe('postal_addresses');
});

it('resolves the address model from config', function() {
    config()->set('byte5-addressable.models.address', CustomAddress::class);

    $model = TestModel::create(['name' => 'Acme']);
    $address = $model->addresses()->create(['city' => 'Berlin']);

    expect($address)->toBeInstanceOf(CustomAddress::class)
        ->and($model->addresses->first())->toBeInstanceOf(CustomAddress::class);
});

it('honours a configurable morph key column name', function() {
    config()->set('byte5-addressable.column_names.model_morph_key', 'owner_id');

    Schema::dropIfExists('addresses');
    $migration = include __DIR__.'/../../database/migrations/create_addresses_table.php.stub';
    $migration->up();

    expect(Schema::hasColumn('addresses', 'owner_id'))->toBeTrue();

    $model = TestModel::create(['name' => 'Acme']);
    $address = $model->addresses()->create(['city' => 'Berlin']);

    expect($model->addresses()->count())->toBe(1)
        ->and($address->addressable->is($model))->toBeTrue();
});

it('casts type to the default AddressType enum', function() {
    $address = Address::factory()->create(['type' => 'billing']);

    expect($address->fresh()->type)->toBe(AddressType::Billing);
});

it('casts type to a custom enum provided via config', function() {
    config()->set('byte5-addressable.type_enum', CustomAddressType::class);

    $address = Address::factory()->create(['type' => 'home']);

    expect($address->fresh()->type)->toBe(CustomAddressType::Home);
});

it('populates the extension line and an address type via the factory', function() {
    $address = Address::factory()->make();

    expect($address->extra)->toBeString()->not->toBeEmpty()
        ->and($address->type)->toBeInstanceOf(AddressType::class);
});

it('normalises the country to an uppercase ISO code', function() {
    $address = Address::factory()->create(['country' => 'de']);

    expect($address->fresh()->country)->toBe('DE');
});

it('keeps type as a plain string when the enum is disabled', function() {
    config()->set('byte5-addressable.type_enum', '');

    $address = Address::factory()->create(['type' => 'anything']);

    expect($address->fresh()->type)->toBe('anything');
});

it('builds a schema.org PostalAddress from the model', function() {
    $address = new Address([
        'street' => 'Pariser Platz 1',
        'extra' => 'Apt. 5',
        'postal' => '10117',
        'city' => 'Berlin',
        'region' => 'Berlin',
        'country' => 'de',
    ]);

    expect($address->toSchemaOrg())
        ->toBeInstanceOf(PostalAddress::class)
        ->and($address->toSchemaOrg()->toArray())->toBe([
            '@type' => 'PostalAddress',
            'streetAddress' => 'Pariser Platz 1',
            'extendedAddress' => 'Apt. 5',
            'postalCode' => '10117',
            'addressLocality' => 'Berlin',
            'addressRegion' => 'Berlin',
            'addressCountry' => 'DE',
        ]);
});
