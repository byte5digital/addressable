<?php

use Byte5\Addressable\App\Facades\Countries;

it('exposes the country list through the facade', function() {
    expect(Countries::list())->toHaveKey('DE', 'Germany');
});
