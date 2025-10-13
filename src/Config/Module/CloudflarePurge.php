<?php

namespace App\Config\Module;

readonly class CloudflarePurge
{
    public function __construct(
        public string $zoneId,
        /** @var string[] */
        public array $sponsorFiles,
        /** @var string[] */
        public array $scheduleFiles,
    ) {
    }
}
