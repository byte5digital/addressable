<?php

use Byte5\Addressable\App\Data\Suggestion;

it('exposes suggestion fields', function () {
    $suggestion = new Suggestion('ChIJ123', 'Berlin, Germany', 'Berlin', 'Germany');

    expect($suggestion->placeId)->toBe('ChIJ123')
        ->and($suggestion->description)->toBe('Berlin, Germany')
        ->and($suggestion->mainText)->toBe('Berlin')
        ->and($suggestion->secondaryText)->toBe('Germany');
});
