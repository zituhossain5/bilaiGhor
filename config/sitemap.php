<?php

return [
    'canonical_url' => env('SITEMAP_CANONICAL_URL', 'https://bilaighor.bd'),

    /*
     * These known starter/demo records must never return to the public sitemap,
     * even if an old database accidentally leaves their status enabled.
     */
    'excluded_category_slugs' => [
        'electronics',
        'fashion',
        'beauty',
        'books',
        'appliances',
        'kids',
    ],

    'excluded_blog_slugs' => [
        'blukat-lens-cokher-jnz-ktta-upkaree-1765860174',
        'km-dame-br-prdar-ntun-faiv-ji-smartfon-bajare-1765860339',
    ],

    /* Only public policy/about pages with clear long-term SEO value. */
    'static_page_slugs' => [
        'about',
        'about-us',
        'privacy-policy',
        'terms-conditions',
        'terms-and-conditions',
        'terms-&-conditions',
        'shipping-policy',
        'delivery-policy',
        'delivery-rules',
        'return-policy',
        'refund-policy',
        'return-refund-policy',
    ],
];
