<?php

namespace App\Config\Module;

readonly class Paypal
{
    public function __construct(
        public string $apiUrl,
        public string $clientId,
        #[\SensitiveParameter]
        public string $secret,
        public string $currency,
    ) {
    }
}
