<?php

namespace App\Config\Module;

readonly class System
{
    public function __construct(
        #[\SensitiveParameter]
        public string $secret,
        public ?string $timezone,
        public ?string $siteOverride,
        public string $themeLocation,
        public string $logDir,
    ) {
    }
}
