<?php

namespace App\Config\Module;

readonly class Theme
{
    public function __construct(
        public string $location,
    ) {
    }
}
