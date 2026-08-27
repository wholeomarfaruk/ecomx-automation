<?php

namespace App\Marketing\Enums;

enum MarketingDestination: string
{
    case META = 'meta';
    case GOOGLE = 'google';
    case GA4 = 'ga4';
    case TIKTOK = 'tiktok';
    case WEBHOOK = 'webhook';
}
