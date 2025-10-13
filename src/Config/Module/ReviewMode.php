<?php

namespace App\Config\Module;

readonly class ReviewMode
{
    public function __construct(
        public bool $showAddress = true,
        public bool $showICE = true,
    ) {
    }
}
