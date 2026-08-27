<?php

namespace App\Marketing\Context;

use Illuminate\Http\Request;

final class MarketingContextBuilder
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function build(
        ?string $deviceFingerprint = null,
        mixed $customer = null,
        mixed $user = null,
    ): MarketingContext {
        return MarketingContext::fromRequest(
            request: $this->request,
            deviceFingerprint: $deviceFingerprint,
            customer: $customer,
            user: $user,
        );
    }
}
