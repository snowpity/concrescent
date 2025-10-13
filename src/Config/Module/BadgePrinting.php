<?php

namespace App\Config\Module;

readonly class BadgePrinting
{
    public function __construct(
        public string $width,
        public string $height,
        public bool $vertical,
        /** @var string[] */
        public array $stylesheet,
        public string $postUrl,
    ) {
    }
}
