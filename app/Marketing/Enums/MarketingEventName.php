<?php

namespace App\Marketing\Enums;

enum MarketingEventName: string
{
    case PAGE_VIEW = 'PageView';
    case VIEW_CONTENT = 'ViewContent';
    case ADD_TO_CART = 'AddToCart';
    case INITIATE_CHECKOUT = 'InitiateCheckout';
    case PURCHASE = 'Purchase';
    case LEAD = 'Lead';
}
