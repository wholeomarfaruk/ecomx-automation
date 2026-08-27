<?php

namespace App\Marketing\Enums;

enum MarketingDeliveryStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case RETRYING = 'retrying';
    case SKIPPED = 'skipped';
}
