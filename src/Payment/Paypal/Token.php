<?php

namespace App\Payment\Paypal;

readonly class Token
{
    public function __construct(
        #[\SensitiveParameter]
        public string $accessToken,
        public string $tokenType,
        public int    $expiresIn,
        public int    $expires,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['access_token'],
            $data['token_type'],
            $data['expires_in'],
            $data['expires'],
        );
    }

    public function isExpired(): bool
    {
        return $this->expires <= time();
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
            'expires' => $this->expires,
        ];
    }
}
