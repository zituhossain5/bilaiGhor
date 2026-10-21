<?php

/*
 * Manual Fraud Check (Admin → Fraud Protection). Page settings that admins change (rate method,
 * risk thresholds, cache time, phone masking) live in App\Support\FraudCheckSettings.
 */
return [

    // A result is kept this long so the page can still show the last answer (with a warning)
    // when a new BD Courier search fails, e.g. when the plan's search limit is reached.
    'keep_days' => 7,

    // New (paid) BD Courier searches allowed per admin per minute. Cached answers don't count.
    'fresh_checks_per_minute' => 10,

    // Recent-search chips shown to each admin.
    'recent_searches' => 5,

    // Display names and local logos (public/assets/images/courier). Couriers BD Courier adds later
    // still show, with the name and logo from the API.
    'couriers' => [
        'pathao'       => ['name' => 'Pathao', 'logo' => 'pathao-logo.png'],
        'steadfast'    => ['name' => 'SteadFast', 'logo' => 'steadfast-logo.png'],
        'redx'         => ['name' => 'RedX', 'logo' => 'redx-logo.png'],
        'paperfly'     => ['name' => 'PaperFly', 'logo' => 'paperfly-logo.png'],
        'parceldex'    => ['name' => 'ParcelDex', 'logo' => 'parceldex-logo.png'],
        'carrybee'     => ['name' => 'CarryBee', 'logo' => 'carrybee-logo.webp'],
        'courrierfast' => ['name' => 'CourierFast', 'logo' => null],
    ],

    /*
     * Optional fields. BD Courier's documented v3 response has only total/success/cancelled/success_ratio
     * per courier, a summary, reports and risk_verdict. If a real response carries more (count snapshot
     * date, a parcel range such as "50+", a report count, an explanation), list its field name here;
     * the first one found is used. Looked up on each courier first, then on data.summary and the top level.
     */
    'fields' => [
        'snapshot_date' => ['snapshot_date', 'snapshot_at', 'counts_as_of', 'counts_date', 'count_date', 'as_of'],
        'parcel_range'  => ['parcel_range', 'total_parcel_range', 'parcels_range', 'parcel_volume', 'volume'],
        'report_count'  => ['fraud_report_count', 'report_count', 'reports_count', 'total_reports'],
        'explanation'   => ['explanation', 'calculation_note', 'note'],
    ],
];
