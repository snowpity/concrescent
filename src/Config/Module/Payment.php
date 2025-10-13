<?php

namespace App\Config\Module;

readonly class Payment
{
    public function __construct(
        public float $salesTax,
    ) {
    }
}
