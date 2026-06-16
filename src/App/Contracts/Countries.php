<?php

namespace Byte5\Addressable\App\Contracts;

interface Countries
{
    /**
     * Country code => localised name, suitable for a dropdown. Restricted to
     * official ISO 3166-1 alpha-2 codes so every entry passes the Country rule.
     *
     * @return array<string, string>
     */
    public function list(?string $locale = null): array;
}
