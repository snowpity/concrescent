<?php

namespace App\Config\Module;

readonly class Event
{
    public function __construct(
        public string $name,
        public string $staffStartDate,
        public string $startDate,
        public string $endDate,
        public string $staffEndDate,
    ) {
    }
}
