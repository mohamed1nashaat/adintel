<?php

namespace App\Services\Integrations\Contracts;

class Account
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly ?string $currency = null,
        public readonly ?string $timezone = null
    ) {}
}
