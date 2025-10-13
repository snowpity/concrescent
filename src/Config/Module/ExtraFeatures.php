<?php

namespace App\Config\Module;

readonly class ExtraFeatures
{
    public function __construct(
        public ?ExtraFeaturesSponsors $sponsors = null,
    ) {
    }
}
