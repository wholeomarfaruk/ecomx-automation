<?php

namespace App\Marketing\Identity;

final readonly class MarketingIdentity
{
    public function __construct(
        public ?string $email = null,
        public ?string $phone = null,

        public ?string $firstName = null,
        public ?string $lastName = null,

        public ?string $country = null,

        public ?string $externalId = null,

        public ?string $deviceFingerprint = null,
    ) {}
}
