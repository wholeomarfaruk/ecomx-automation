<?php

return [
    // Active theme slug — matches storage/app/private/themes/{theme}/page-sections.json
    // and the theme's identity in resources/{theme}/theme.json.
    'active_theme' => 'ecomx-anyniche',

    'brand' => 'AnyNiche',
    'domain' => 'anyniche.example.com',
    'phone' => '+8801700000000',
    'unsplash' => 'https://images.unsplash.com/',
    'palettes' => ['neutral'],
    'trust' => [
        ['val'=>'10,000+','label'=>'Happy customers'],
        ['val'=>'★ 4.8 / 5','label'=>'From verified reviews'],
        ['val'=>'24–48h','label'=>'Fast local delivery'],
        ['val'=>'100%','label'=>'Quality guaranteed'],
    ],

    // Registry of reusable, lazy-loaded Livewire section components.
    // key => Livewire component tag name (App\Livewire\EcomxAnyniche\Sections\*).
    'sections' => [
        'trust' => 'ecomx-anyniche.sections.trust',
        'hero-slider' => 'ecomx-anyniche.sections.hero-slider',
        'category-carousel' => 'ecomx-anyniche.sections.category-carousel',
        'category-row-1' => 'ecomx-anyniche.sections.category-row',
        'promo-strip-banner' => 'ecomx-anyniche.sections.promo-strip-banner',
        'category-row-2' => 'ecomx-anyniche.sections.category-row',
        'promos-grid' => 'ecomx-anyniche.sections.promos-grid',
        'browse-all' => 'ecomx-anyniche.sections.browse-all',
        'category-row-3' => 'ecomx-anyniche.sections.category-row',
        'category-row-4' => 'ecomx-anyniche.sections.category-row',
        'category-row-5' => 'ecomx-anyniche.sections.category-row',
        'discover-chips' => 'ecomx-anyniche.sections.discover-chips',
        'privacy-content' => 'ecomx-anyniche.sections.privacy-content',
        'about-hero' => 'ecomx-anyniche.sections.about-hero',
        'about-stats' => 'ecomx-anyniche.sections.about-stats',
        'about-story' => 'ecomx-anyniche.sections.about-story',
        'about-features' => 'ecomx-anyniche.sections.about-features',
        'about-cta' => 'ecomx-anyniche.sections.about-cta',
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
                'hero-slider',
                'category-carousel',
                'trust',
                'category-row-1',
                'promo-strip-banner',
                'category-row-2',
                'promos-grid',
                'browse-all',
                'category-row-3',
                'category-row-4',
                'category-row-5',
                'discover-chips',
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
        'track.show' => [
            'label' => 'Order Details',
            'icon' => 'icon-truck',
            'route' => 'track.show',
            'sections' => [],
        ],
        'checkout' => [
            'label' => 'Checkout',
            'icon' => 'icon-shopping-cart',
            'route' => 'checkout',
            'sections' => [],
        ],
        'account' => [
            'label' => 'My Account',
            'icon' => 'icon-user',
            'route' => 'account',
            'sections' => [],
        ],
        'about' => [
            'label' => 'About Us',
            'icon' => 'icon-info',
            'route' => 'about',
            'sections' => [
                'about-hero',
                'about-stats',
                'about-story',
                'about-features',
                'about-cta',
            ],
        ],
        'privacy-policy' => [
            'label' => 'Privacy Policy',
            'icon' => 'icon-shield',
            'route' => 'privacy-policy',
            'sections' => [
                'privacy-content',
            ],
        ],
    ],
];
