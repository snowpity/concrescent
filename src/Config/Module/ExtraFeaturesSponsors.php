<?php

namespace App\Config\Module;

readonly class ExtraFeaturesSponsors
{
    public function __construct(
        public string $nameCredit,
        public string $publishableCredit,
    ) {
    }
}
