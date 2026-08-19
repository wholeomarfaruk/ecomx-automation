<?php

return [
    // Active theme slug — matches public/{theme}/config/page-sections.json
    // and the theme's identity in resources/{theme}/theme.json.
    'active_theme' => 'ecomx-fashion',

    'brand' => 'Seldom Fashion',
    'domain' => 'seldomfashion.com',
    'phone' => '+8801700000000',
    'unsplash' => 'https://images.unsplash.com/',
    'palettes' => ['terracotta','rust','midnight','coral','peach','blush','sage','olive','wine','slate'],
    'trust' => [
        ['val'=>'40,000+','label'=>'Happy customers, 64 districts'],
        ['val'=>'★ 4.8 / 5','label'=>'From 2,314 verified reviews'],
        ['val'=>'24–48h','label'=>'Delivery inside Dhaka'],
        ['val'=>'100%','label'=>'Authenticity guaranteed'],
    ],

    // Registry of reusable, lazy-loaded Livewire section components.
    // key => Livewire component tag name (App\Livewire\EcomxFashion\Sections\*).
    'sections' => [
        'hero'          => 'ecomx-fashion.sections.hero',
        'marquee'       => 'ecomx-fashion.sections.marquee',
        'categories'    => 'ecomx-fashion.sections.categories',
        'flash-sale'    => 'ecomx-fashion.sections.flash-sale',
        'promo-strip'   => 'ecomx-fashion.sections.promo-strip',
        'trending'      => 'ecomx-fashion.sections.trending',
        'shop-by-style' => 'ecomx-fashion.sections.shop-by-style',
        'reviews'       => 'ecomx-fashion.sections.reviews',
        'instagram'     => 'ecomx-fashion.sections.instagram',
        'why-faq'       => 'ecomx-fashion.sections.why-faq',
    ],

    // Registry of pages for this theme: key => [label, icon, route, sections].
    // 'sections' is the ordered list of section keys (from 'sections' above)
    // rendered on that page.
    'pages' => [
        'home' => [
            'label' => 'Home',
            'icon' => 'icon-home',
            'route' => 'home',
            'sections' => [
                'hero',
                'marquee',
                'categories',
                'flash-sale',
                'promo-strip',
                'trending',
                'shop-by-style',
                'reviews',
                'instagram',
                'why-faq',
            ],
        ],
        'shop' => [
            'label' => 'Shop',
            'icon' => 'icon-grid',
            'route' => 'shop',
            'sections' => [],
        ],
        'category' => [
            'label' => 'Category',
            'icon' => 'icon-layers',
            'route' => 'category',
            'sections' => [],
        ],
        'product' => [
            'label' => 'Product',
            'icon' => 'icon-box',
            'route' => 'product',
            'sections' => [],
        ],
        'reviews' => [
            'label' => 'Reviews',
            'icon' => 'icon-star',
            'route' => 'reviews',
            'sections' => [],
        ],
        'track' => [
            'label' => 'Track Order',
            'icon' => 'icon-truck',
            'route' => 'track',
            'sections' => [],
        ],
        'checkout' => [
            'label' => 'Checkout',
            'icon' => 'icon-shopping-cart',
            'route' => 'checkout',
            'sections' => [],
        ],
    ],
];
