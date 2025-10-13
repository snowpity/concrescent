<?php

namespace App\Config\Module;

readonly class Cloudflare
{
    public function __construct(
        #[\SensitiveParameter]
        public string $bearerToken,
        public ?CloudflarePurge $purge = null,
    ) {
    }
}
