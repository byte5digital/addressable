# Addressable

A small Laravel package for attaching schema.org-aligned postal addresses to any
Eloquent model via a polymorphic relationship.

## Requirements

- PHP 8.2+
- Laravel 12 or 13

## Installation

Install via Composer:

```bash
composer require byte5/addressable
```

The service provider is auto-discovered. Publish the migration, then migrate:

```bash
php artisan vendor:publish --tag=byte5-addressable/migrations
php artisan migrate
```

The migration is **published rather than loaded from the package** so you can edit
it before migrating — see *Owner morph key* below for UUID/ULID keys.

Publishing the config is **optional**; its defaults are merged automatically.
Publish it only when you want to change a default:

```bash
php artisan vendor:publish --tag=byte5-addressable/config
```

## Configuration

The config is merged under the `byte5-addressable` key. Publish it to change any
default:

```bash
php artisan vendor:publish --tag=byte5-addressable/config
```

```php
return [
    'models' => [
        'address' => \Byte5\Addressable\App\Models\Address::class,
    ],
    'table_names' => [
        'addresses' => 'addresses',
    ],
    'column_names' => [
        'model_morph_key' => 'addressable_id',
    ],
    'type_enum' => \Byte5\Addressable\App\Enums\AddressType::class, // or '' to disable
];
```

> Anything that affects the schema (`table_names`, `column_names`) must be set,
> and the migration edited if needed, **before** running it. Changing it
> afterwards requires a rollback and re-migrate.

### Models — swap the Address model

`models.address` is the Eloquent model used for addresses. To customise it (most
commonly to give addresses UUID/ULID primary keys), extend the package model, add
the relevant Laravel trait, and register your class:

```php
namespace App\Models;

use Byte5\Addressable\App\Models\Address as BaseAddress;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Address extends BaseAddress
{
    use HasUuids;
}
```

```php
// config/byte5-addressable.php
'models' => [
    'address' => \App\Models\Address::class,
],
```

The `HasAddresses` trait and the factory both resolve the model from this config
value, so your subclass is used everywhere.

### Owner morph key — `uuid` / `ulid`

The polymorphic key that points at the **owner** model defaults to a
`bigint unsigned` column named `addressable_id`. Two things control it:

- `column_names.model_morph_key` — the column **name** (rename to e.g.
  `addressable_uuid` if you prefer).
- The column **type** lives in the published migration. If your owner models use
  UUID/ULID primary keys, edit it before migrating:

  ```php
  // database/migrations/..._create_addresses_table.php
  // $table->unsignedBigInteger($morphKey)->nullable();
  $table->uuid($morphKey)->nullable();   // or ->ulid($morphKey)
  ```

The Address model's **own** primary key is independent of this — it stays a
`bigint` unless you swap in a model using `HasUuids`/`HasUlids` (see *Models*).

### Table name

`table_names.addresses` is the table used to store addresses (default
`addresses`). Both the migration and the `Address` model read it.

### Address type enum

The `type` column is cast to a string-backed enum. The package ships a default
`Byte5\Addressable\App\Enums\AddressType` (`billing`, `shipping`, `primary`). You can:

- **Replace it** with your own enum to match your application's address roles:

  ```php
  // config/byte5-addressable.php
  'type_enum' => \App\Enums\MyAddressType::class,
  ```

- **Disable casting** and keep `type` a plain string:

  ```php
  'type_enum' => '',
  ```

Your enum must be **string-backed**, and its backing values must match the values
already stored in the `type` column — switching the enum does **not** migrate
existing data. Rows with a `type` that isn't a valid case will throw on read.

No migration change is needed: the column stays a `string`; the enum is only the
application-layer representation.

## Usage

Add the `HasAddresses` trait to any model that should own addresses:

```php
use Byte5\Addressable\App\Concerns\HasAddresses;

class User extends Model
{
    use HasAddresses;
}
```

You then get:

```php
// All addresses (morphMany)
$user->addresses;
$user->addresses()->create([
    'street' => 'Main 10',
    'postal' => '10115',
    'city' => 'Berlin',
    'region' => 'Berlin',
    'country' => 'DE',
    'type' => 'billing', // optional role/label
]);

// The most recently attached address (morphOne)
$user->latestAddress;

// The owning model from an address (morphTo)
$address->addressable;
```

## Creating addresses

`$model->addAddress($data, $type)` is the standardised entry point for persisting a
new address. It accepts either an `AddressData` DTO or a loose attribute array, and
an optional `AddressType` (or its backing string) that overrides whatever type is
already on the data:

```php
use Byte5\Addressable\App\Data\AddressData;
use Byte5\Addressable\App\Enums\AddressType;

// From a typed DTO
$user->addAddress(new AddressData(
    street: 'Pariser Platz 1',
    postal: '10117',
    city:   'Berlin',
    country: 'DE',
), AddressType::Billing);

// From a loose array — internally calls AddressData::fromArray()
$user->addAddress([
    'street'  => 'Pariser Platz 1',
    'postal'  => '10117',
    'city'    => 'Berlin',
    'country' => 'DE',
    'type'    => 'billing',
]);
```

Both forms return the persisted `Address` instance.

### `AddressData` — the write DTO

`AddressData` is a readonly DTO that carries the nine address fields (`type`,
`street`, `extra`, `postal`, `city`, `region`, `latitude`, `longitude`, `country`).
All fields are optional (default `null`).

The lookup and schema.org DTOs provide typed bridges:

```php
// From a resolved Google Place
$details = AddressLookup::details($placeId);   // PlaceDetails
$data    = $details->toAddressData(AddressType::Shipping);

// From a schema.org PostalAddress DTO
$postal = $address->toSchemaOrg();             // PostalAddress
$data   = $postal->toAddressData(AddressType::Billing);
```

Pass the resulting `AddressData` straight to `addAddress()`.

### Swapping the creation implementation

Address creation is backed by `Byte5\Addressable\App\Contracts\CreatesAddresses`
(single method: `create(Model $owner, AddressData $data): Address`). The package
binds the default `AddressCreator` service as a singleton, but you can replace it in
any service provider:

```php
use Byte5\Addressable\App\Contracts\CreatesAddresses;

$this->app->bind(CreatesAddresses::class, MyDedupingAddressCreator::class);
```

The package enforces **no deduplication, per-type uniqueness, or default/primary
address policy** — that is intentional. Add whatever cardinality rules your
application needs here.

## schema.org mapping

The columns map to [schema.org/PostalAddress](https://schema.org/PostalAddress):

| Column                  | schema.org                          |
| ----------------------- | ----------------------------------- |
| `street`                | `streetAddress`                     |
| `extra`                 | `extendedAddress`                   |
| `postal`                | `postalCode`                        |
| `city`                  | `addressLocality`                   |
| `region`                | `addressRegion`                     |
| `country`               | `addressCountry` (ISO 3166-1 alpha-2) |
| `latitude`, `longitude` | `GeoCoordinates` (on a `Place.geo`) |

`latitude` / `longitude` are stored as `decimal(10,8)` / `decimal(11,8)` and cast
to `decimal:8`.

### Emitting a `PostalAddress`

`$address->toSchemaOrg()` returns a `PostalAddress` DTO that renders to either a
PHP array or a JSON-LD string:

```php
$address->toSchemaOrg()->toArray();
// [
//     '@type' => 'PostalAddress',
//     'streetAddress' => 'Pariser Platz 1',
//     'postalCode' => '10117',
//     'addressLocality' => 'Berlin',
//     'addressCountry' => 'DE',
//     // null fields omitted; no '@context'
// ]

$address->toSchemaOrg()->toJsonLd();
// {"@context":"https://schema.org","@type":"PostalAddress","streetAddress":"Pariser Platz 1",…}
```

`toArray()` is a **fragment** (`@type`, no `@context`) — nest it inside a parent
entity such as `Organization`/`Person`. `toJsonLd()` is a **standalone** document
(includes `@context`) — drop it straight into a `<script type="application/ld+json">`
tag. Latitude/longitude are intentionally excluded, since a schema.org
`PostalAddress` has no geo property (those belong on a `Place.geo`).

## Form validation rules

Three Laravel validation rules ship for validating address input (e.g. in a
`FormRequest`). All three skip empty values, so pair them with `required` /
`nullable` / `string`, which own emptiness.

| Rule           | Checks                                                                                                        | Needs an API?                                          |
| -------------- | ------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| `Country`      | a valid ISO 3166-1 alpha-2 country code (case-insensitive)                                                    | no                                                     |
| `PostalFormat` | the postal code matches the format for a given country (no-op for unknown countries or those without one)     | no                                                     |
| `AddressExists`| the address is deliverable, via the configured validation provider                                           | yes — see [Validating an address](#validating-an-address) |

```php
use Byte5\Addressable\App\Rules\AddressExists;
use Byte5\Addressable\App\Rules\Country;
use Byte5\Addressable\App\Rules\PostalFormat;

public function rules(): array
{
    return [
        'country' => ['required', 'string', new Country()],
        'postal'  => ['required', 'string', new PostalFormat($this->input('country'))],

        // Deliverability check: attach to ONE field. It reads the sibling
        // street/postal/city/region/country inputs and makes a single
        // (billable) provider call per validation run.
        'street'  => ['required', 'string', new AddressExists()],
    ];
}
```

The `AddressRules` facade is a small factory for the same rules:

```php
use Byte5\Addressable\App\Facades\AddressRules;

AddressRules::country();           // new Country()
AddressRules::postalFormat('DE');  // new PostalFormat('DE')
AddressRules::exists();            // new AddressExists()
```

`AddressExists` reads the address from sibling fields of the validation payload,
defaulting to the keys `street`, `postal`, `city`, `region`, `country`. Pass a map
to point at differently-named inputs (dot notation supported):

```php
new AddressExists(['postal' => 'zip_code', 'country' => 'address.country']);
```

Because it calls the validation provider, the `validation.pass_on_outage` config
applies: when the provider is unreachable the rule throws by default, or passes the
value through when `pass_on_outage` is `true`.

Messages live in the `byte5-addressable` translation namespace (keys `country`,
`postal_format`, `address_exists`). Override them by creating
`lang/vendor/byte5-addressable/{locale}/validation.php` in your app.

## Country list

`Countries::list()` returns an ISO 3166-1 alpha-2 `code => name` map, localised and
ordered via commerceguys/addressing — ready to drop into a `<select>`. Every key is
a code the `Country` rule accepts.

```php
use Byte5\Addressable\App\Facades\Countries;

Countries::list();      // ['DE' => 'Germany', 'FR' => 'France', …] in the app locale
Countries::list('de');  // ['DE' => 'Deutschland', 'FR' => 'Frankreich', …]
```

## Address lookup (autocomplete + details)

Type-ahead address suggestions and place resolution via a pluggable provider
(Google Places by default). The provider is selected in config; a future custom
frontend component will call these through your own controller, keeping the API
key server-side.

### Configuration

```php
// config/byte5-addressable.php
// Autocomplete + geocoding (AddressLookup::suggest/details)
'lookup' => [
    'provider' => env('ADDRESSABLE_LOOKUP_PROVIDER', 'google'),
    'providers' => [
        'google' => [
            'key' => env('ADDRESSABLE_LOOKUP_GOOGLE_KEY'),
            'language' => env('ADDRESSABLE_LOOKUP_GOOGLE_LANGUAGE'), // Places languageCode; falls back to app locale
            'region' => env('ADDRESSABLE_LOOKUP_GOOGLE_REGION'),     // region bias (regionCode)
            'country' => env('ADDRESSABLE_LOOKUP_GOOGLE_COUNTRY'),   // single ISO 3166-1 alpha-2 code (set an array in the published config for multiple)
        ],
    ],
],

// Address validation (AddressValidator::validate + the AddressExists rule) — separate provider + key
'validation' => [
    'provider' => env('ADDRESSABLE_VALIDATION_PROVIDER', 'google'),

    // How the AddressExists rule reacts when the provider is unreachable:
    // false (default) surfaces the error; true lets the address through.
    'pass_on_outage' => env('ADDRESSABLE_VALIDATION_PASS_ON_OUTAGE', false),

    'providers' => [
        'google' => [
            'key' => env('ADDRESSABLE_VALIDATION_GOOGLE_KEY'),
        ],
    ],
],
```

Lookup and validation are independent: each has its own config-selected driver
(`AddressLookupManager` / `AddressValidationManager`) and its own API key, so you can
mix providers or use one key with both Google APIs enabled.

Set `ADDRESSABLE_LOOKUP_GOOGLE_KEY` in your `.env` (the project must have the
Places API **(New)** enabled). See `.env.example` for every supported variable.

**Language:** when no language is given per call or in config, results default to
the application locale (`app()->getLocale()`), resolved per request. A per-call
`language` option or the config/env value takes precedence. Use values Google
accepts as a `languageCode` (e.g. `de`, `en`, `pt-BR`).

### Usage

```php
use Byte5\Addressable\App\Facades\AddressLookup;

// 1. Suggestions as the user types
$suggestions = AddressLookup::suggest('Branden');
// => Byte5\Addressable\App\Data\Suggestion[] { placeId, description, mainText, secondaryText }

// 2. Resolve the chosen suggestion into a structured address
$details = AddressLookup::details($suggestions[0]->placeId);
// => Byte5\Addressable\App\Data\PlaceDetails (or null if not found)

// 3. Persist it — toArray() matches the Address columns
$user->addresses()->create($details->toArray());
```

Per-call overrides (and `AddressLookup::driver('google')`) are available:

```php
AddressLookup::suggest('Haupt', ['country' => 'DE', 'language' => 'de']);
```

### Validating an address

Check whether a structured address actually exists / is deliverable via the
**`AddressValidator`** facade (backed by `AddressValidationManager`, separate from
lookup). It uses Google's **Address Validation API** — a separate SKU that must be
enabled in your Cloud project. The driver implements the
`Byte5\Addressable\App\Contracts\ValidatesAddresses` capability:

```php
use Byte5\Addressable\App\Data\AddressInput;
use Byte5\Addressable\App\Facades\AddressValidator;

$validation = AddressValidator::validate(new AddressInput(
    street: 'Pariser Platz 1',
    postal: '10117',
    city: 'Berlin',
    country: 'DE', // ISO 3166-1 alpha-2 (regionCode)
));

$validation->valid;             // normalised: deliverable / exists (every provider)
$validation->provider;          // 'google'
$validation->formattedAddress;  // standardised address
$validation->raw;               // full provider payload

// Typed Google specifics — narrow to the provider's result:
use Byte5\Addressable\App\Data\GoogleAddressValidation;

if ($validation instanceof GoogleAddressValidation) {
    $validation->granularity;              // PREMISE | SUB_PREMISE | ROUTE | LOCALITY | OTHER | ...
    $validation->complete;                 // Google addressComplete
    $validation->hasUnconfirmedComponents;
    $validation->hasInferredComponents;
    $validation->hasReplacedComponents;
}
```

The base `AddressValidation` is **provider-agnostic** (`valid`, `provider`,
`formattedAddress`, `raw`); each driver maps its native verdict into `valid`. The Google
driver returns a `GoogleAddressValidation` subclass that adds the typed verdict fields —
through the facade you get the base type, so narrow with `instanceof` to read them (or
inject the driver directly, which returns the subclass). For Google, `valid` is `true`
when the address validates to `PREMISE`/`SUB_PREMISE` granularity, `addressComplete` is
true, and there are no unconfirmed components.

> Validation is a separate capability: `validate()` lives on `ValidatesAddresses`,
> not the core `AddressLookup` contract — so a custom driver only implements it if its
> provider supports validation.

### Events

Every lookup dispatches backend events from the driver, so they fire no matter how
the lookup is triggered (facade, your own endpoint, a UI component). Use them for
usage/cost tracking or to record selected addresses. Nothing is dispatched when a
request fails.

| Event | Fires when | Payload |
| ----- | ---------- | ------- |
| `Byte5\Addressable\App\Events\AddressSuggestionsRequested` | a `suggest()` request completes | `provider`, `query`, `options`, `count` |
| `Byte5\Addressable\App\Events\AddressDetailsRequested` | a `details()` request completes | `provider`, `placeId`, `options`, `found` |
| `Byte5\Addressable\App\Events\AddressValidationRequested` | a `validate()` request completes | `provider`, `address`, `options`, `valid` |
| `Byte5\Addressable\App\Events\AddressResolved` | `details()` resolves a place | `provider`, `placeId`, `details` (`PlaceDetails`) |

```php
use Byte5\Addressable\App\Events\AddressResolved;
use Illuminate\Support\Facades\Event;

Event::listen(function (AddressResolved $event) {
    logger()->info("Resolved {$event->placeId} via {$event->provider}", $event->details->toArray());
});
```

> `AddressSuggestionsRequested` fires on every keystroke-driven `suggest()` call —
> debounce or sample in high-traffic listeners.

### Building an autocomplete UI (reference)

The package is **headless** — it ships no UI component and adds no frontend
dependency, so you build the dropdown in whatever your app already uses (Livewire,
Alpine, Vue, Inertia, …). The only integration surface is the `AddressLookup`
facade; always call it **server-side** so your API key never reaches the browser.

The flow is the same in every stack:

1. user types → `AddressLookup::suggest($query)` → render the `Suggestion[]`
2. user picks one → `AddressLookup::details($placeId)` → `PlaceDetails`
3. fill your form fields from the result via shared state — no events needed.
   `PlaceDetails` exposes `street`, `postal`, `city`, `region`, `country`,
   `latitude`, `longitude`, and `toArray()` matches the `Address` columns.

The snippets below are **reference starting points to copy and adapt**, not shipped
components.

#### Livewire

```php
use Byte5\Addressable\App\Facades\AddressLookup;
use Livewire\Component;

class AddressForm extends Component
{
    public string $query = '';

    /** @var array<int, array{placeId: string, description: string}> */
    public array $suggestions = [];

    // Bound form fields — filled from the chosen address.
    public ?string $street = null;
    public ?string $postal = null;
    public ?string $city = null;
    public ?string $region = null;
    public ?string $country = null;

    public function updatedQuery(): void
    {
        // Map to plain arrays — Livewire only serialises primitive/array props,
        // not the Suggestion DTO.
        $this->suggestions = strlen($this->query) >= 3
            ? array_map(
                fn ($s) => ['placeId' => $s->placeId, 'description' => $s->description],
                AddressLookup::suggest($this->query),
            )
            : [];
    }

    public function select(string $placeId): void
    {
        if ($details = AddressLookup::details($placeId)) {
            $this->street  = $details->street;
            $this->postal  = $details->postal;
            $this->city    = $details->city;
            $this->region  = $details->region;
            $this->country = $details->country;
        }

        $this->suggestions = [];
    }

    public function render()
    {
        return view('livewire.address-form');
    }
}
```

```blade
{{-- resources/views/livewire/address-form.blade.php --}}
<div>
    <input type="text" wire:model.live.debounce.300ms="query" placeholder="Search address…">

    @if ($suggestions)
        <ul>
            @foreach ($suggestions as $suggestion)
                <li>
                    <button type="button" wire:click="select('{{ $suggestion['placeId'] }}')">
                        {{ $suggestion['description'] }}
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <input type="text" wire:model="street"  placeholder="Street">
    <input type="text" wire:model="postal"  placeholder="Postal code">
    <input type="text" wire:model="city"    placeholder="City">
    <input type="text" wire:model="region"  placeholder="Region">
    <input type="text" wire:model="country" placeholder="Country">
</div>
```

#### Alpine + JSON endpoint

When the UI runs in the browser (Alpine, Vue, …) it can't call the facade
directly, so add a thin endpoint. **Protect it** (auth + throttle) — it spends your
Google quota.

```php
use Byte5\Addressable\App\Facades\AddressLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/address/suggest', fn (Request $r) => AddressLookup::suggest($r->string('q')));
    Route::get('/address/details/{placeId}', fn (string $placeId) => AddressLookup::details($placeId));
});
```

```blade
<div x-data="addressLookup()">
    <input type="text" x-model="query" @input.debounce.300ms="search()" placeholder="Search address…">

    <ul x-show="suggestions.length">
        <template x-for="s in suggestions" :key="s.placeId">
            <li><button type="button" @click="select(s.placeId)" x-text="s.description"></button></li>
        </template>
    </ul>

    <input type="text" x-model="form.street"  placeholder="Street">
    <input type="text" x-model="form.postal"  placeholder="Postal code">
    <input type="text" x-model="form.city"    placeholder="City">
    <input type="text" x-model="form.region"  placeholder="Region">
    <input type="text" x-model="form.country" placeholder="Country">
</div>

<script>
function addressLookup() {
    return {
        query: '',
        suggestions: [],
        form: { street: '', postal: '', city: '', region: '', country: '' },

        async search() {
            if (this.query.length < 3) { this.suggestions = []; return; }
            this.suggestions = await (await fetch(`/address/suggest?q=${encodeURIComponent(this.query)}`)).json();
        },

        async select(placeId) {
            const details = await (await fetch(`/address/details/${placeId}`)).json();
            if (details) this.form = {
                street: details.street, postal: details.postal, city: details.city,
                region: details.region, country: details.country,
            };
            this.suggestions = [];
        },
    };
}
</script>
```

## Testing

```bash
composer test   # Pest test suite
composer stan   # PHPStan static analysis
```
