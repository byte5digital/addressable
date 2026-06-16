<?php

namespace Byte5\Addressable\Tests\Fixtures;

use Byte5\Addressable\App\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    use HasAddresses;

    protected $table = 'test_models';

    protected $guarded = [];
}
