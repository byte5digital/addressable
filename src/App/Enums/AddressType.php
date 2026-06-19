<?php

namespace Byte5\Addressable\App\Enums;

enum AddressType: string
{
    case Billing  = 'billing';
    case Shipping = 'shipping';
    case Home     = 'home';
    case Work     = 'work';
}
