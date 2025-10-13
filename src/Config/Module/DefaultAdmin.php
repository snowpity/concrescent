<?php

namespace App\Config\Module;

readonly class DefaultAdmin
{
    public function __construct(
        public string $name,
        public string $username,
        #[\SensitiveParameter]
        public string $password,
    ) {
    }
}
