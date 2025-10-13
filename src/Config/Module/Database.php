<?php

namespace App\Config\Module;

readonly class Database
{
    public function __construct(
        public string $host,
        public string $username,
        #[\SensitiveParameter]
        public string $password,
        public string $database,
        public string $timezone,
    ) {
    }
}
