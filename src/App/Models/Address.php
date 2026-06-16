<?php

namespace Byte5\Addressable\App\Models;

use Byte5\Addressable\Database\Factories\AddressFactory;
use Byte5\Addressable\App\Support\Config;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    /**
     * The attributes that are not mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::addressesTable() ?: parent::getTable();
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    /**
     * Normalise the country to an uppercase ISO 3166-1 alpha-2 code.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function country(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value === null ? null : strtoupper($value),
        );
    }

    /**
     * The parent model this address belongs to.
     *
     * @return MorphTo<Model, $this>
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo(
            'addressable',
            'addressable_type',
            Config::morphKey(),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        $casts = [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];

        if ($enum = Config::typeEnum()) {
            $casts['type'] = $enum;
        }

        return $casts;
    }
}
