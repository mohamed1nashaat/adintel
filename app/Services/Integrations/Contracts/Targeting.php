<?php

namespace App\Services\Integrations\Contracts;

class Targeting
{
    public function __construct(
        public readonly array $locations = [],
        public readonly array $interests = [],
        public readonly array $demographics = [],
        public readonly array $behaviors = [],
        public readonly ?array $customAudiences = null,
        public readonly ?array $excludedAudiences = null
    ) {}
}
