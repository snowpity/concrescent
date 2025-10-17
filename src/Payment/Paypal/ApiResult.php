<?php

namespace App\Payment\Paypal;

readonly class ApiResult
{
    public function __construct(
        #[\SensitiveParameter]
        public mixed $data,
        public int $httpStatus,
    ) {
    }
}
