<?php

namespace App\Marketing\Identity;

/**
 * What we know about the person behind an event, destination-agnostic and
 * un-normalized — each destination adapter applies its own normalization
 * and hashing (see MetaPayloadBuilder).
 */
final readonly class MarketingIdentity
{
    public function __construct(
        public ?string $email = null,

        /** E.164 with leading "+", e.g. +8801761234567. */
        public ?string $phone = null,

        public ?string $firstName = null,
        public ?string $lastName = null,

        public ?string $gender = null,

        /** Y-m-d */
        public ?string $dateOfBirth = null,

        public ?string $city = null,
        public ?string $state = null,
        public ?string $zip = null,

        /** ISO 3166-1 alpha-2, e.g. BD. */
        public ?string $country = null,

        /**
         * Stable first-party ids for this person, most specific first:
         * customer_<id> / user_<id> once known, plus device_<fingerprint>
         * always. Sending the device id on anonymous AND identified events
         * is what lets a destination link a shopper's pre-purchase
         * browsing to their eventual purchase.
         *
         * @var string[]
         */
        public array $externalIds = [],

        public ?string $deviceFingerprint = null,
    ) {}
}
