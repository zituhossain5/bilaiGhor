<?php

/*
|--------------------------------------------------------------------------
| Courier parcel tracking
|--------------------------------------------------------------------------
| Each courier's raw statuses are normalized into one shared set (below) for the parcel page,
| badges, tabs and the dashboard. This only affects display: whether a courier status moves
| the ORDER to Delivered/Cancelled is still decided by the existing mappings
| (SteadfastWebhookStatus, RedXService::mapStatusToOrderStatus, PathaoStatus).
| Raw statuses not listed here show as "unknown" and are logged, so they can be added.
*/

return [

    // Normalized statuses, in rough lifecycle order. step = stepper position (1 Sent … 4 Delivered),
    // tone = badge colour, final = the parcel's journey is over.
    'statuses' => [
        'sent'                => ['en' => 'Sent to courier',  'bn' => 'কুরিয়ারে পাঠানো',     'tone' => 'info',    'step' => 1, 'final' => false],
        'in_review'           => ['en' => 'In review',        'bn' => 'রিভিউতে',              'tone' => 'info',    'step' => 1, 'final' => false],
        'approved'            => ['en' => 'Approved',         'bn' => 'অনুমোদিত',             'tone' => 'info',    'step' => 2, 'final' => false],
        'in_progress'         => ['en' => 'In progress',      'bn' => 'চলমান',                'tone' => 'info',    'step' => 2, 'final' => false],
        'pending_pickup'      => ['en' => 'Pending pickup',   'bn' => 'পিকআপের অপেক্ষায়',     'tone' => 'info',    'step' => 2, 'final' => false],
        'picked_up'           => ['en' => 'Picked up',        'bn' => 'পিকআপ হয়েছে',          'tone' => 'info',    'step' => 2, 'final' => false],
        'in_transit'          => ['en' => 'In transit',       'bn' => 'পথে আছে',              'tone' => 'info',    'step' => 2, 'final' => false],
        'at_hub'              => ['en' => 'At hub',           'bn' => 'হাবে আছে',             'tone' => 'info',    'step' => 2, 'final' => false],
        'out_for_delivery'    => ['en' => 'With rider',       'bn' => 'রাইডারের কাছে',        'tone' => 'primary', 'step' => 3, 'final' => false],
        'delivered'           => ['en' => 'Delivered',        'bn' => 'ডেলিভারড',             'tone' => 'success', 'step' => 4, 'final' => true],
        'partially_delivered' => ['en' => 'Partly delivered', 'bn' => 'আংশিক ডেলিভারড',       'tone' => 'success', 'step' => 4, 'final' => true],
        'on_hold'             => ['en' => 'On hold',          'bn' => 'হোল্ডে',               'tone' => 'warning', 'step' => 3, 'final' => false],
        'returning'           => ['en' => 'Returning',        'bn' => 'ফেরত আসছে',            'tone' => 'danger',  'step' => 3, 'final' => false],
        'returned'            => ['en' => 'Returned',         'bn' => 'ফেরত এসেছে',           'tone' => 'danger',  'step' => 4, 'final' => true],
        'cancelled'           => ['en' => 'Cancelled',        'bn' => 'বাতিল',                'tone' => 'danger',  'step' => 4, 'final' => true],
        'unknown'             => ['en' => 'Unknown',          'bn' => 'অজানা',                'tone' => 'secondary', 'step' => 1, 'final' => false],
    ],

    // Raw courier status (lower-cased, spaces/hyphens → "_") => normalized status
    'map' => [
        'steadfast' => [
            'in_review'                          => 'in_review',
            'pending'                            => 'in_progress',   // Steadfast: "not delivered or cancelled yet" (any stage)
            'hold'                               => 'on_hold',
            'delivered_approval_pending'         => 'delivered',
            'partial_delivered_approval_pending' => 'partially_delivered',
            'cancelled_approval_pending'         => 'returning',
            'unknown_approval_pending'           => 'unknown',
            'delivered'                          => 'delivered',
            'partial_delivered'                  => 'partially_delivered',
            'cancelled'                          => 'cancelled',
            'unknown'                            => 'unknown',
        ],
        'pathao' => [
            'pending'                    => 'sent',
            'order_created'              => 'sent',
            'created'                    => 'sent',
            'pickup_requested'           => 'pending_pickup',
            'assigned_for_pickup'        => 'pending_pickup',
            'picked'                     => 'picked_up',
            'pickup_failed'              => 'on_hold',
            'pickup_cancelled'           => 'cancelled',
            'at_the_sorting_hub'         => 'at_hub',
            'in_transit'                 => 'in_transit',
            'received_at_last_mile_hub'  => 'at_hub',
            'assigned_for_delivery'      => 'out_for_delivery',
            'delivered'                  => 'delivered',
            'partial_delivery'           => 'partially_delivered',
            'partial_delivered'          => 'partially_delivered',
            'return'                     => 'returned',
            'returned'                   => 'returned',
            'paid_return'                => 'returned',
            'delivery_failed'            => 'returning',
            'on_hold'                    => 'on_hold',
            'hold'                       => 'on_hold',
            'cancelled'                  => 'cancelled',
        ],
        'redx' => [
            'pickup_pending'       => 'pending_pickup',
            'picked_up'            => 'picked_up',
            'in_transit'           => 'in_transit',
            'ready_for_delivery'   => 'at_hub',
            'delivery_in_progress' => 'out_for_delivery',
            'delivered'            => 'delivered',
            'partial_delivered'    => 'partially_delivered',
            'agent_hold'           => 'on_hold',
            'agent_returning'      => 'returning',
            'returned'             => 'returned',
            'agent_area_change'    => 'in_transit',
            'delivery_failed'      => 'returning',
            'cancelled'            => 'cancelled',
            'parcel_cancelled'     => 'cancelled',
        ],
    ],

    // Steadfast `tracking_update` carries only free text. These patterns set the parcel's DISPLAY status
    // (e.g. "With rider") — never the order status. First match wins; no match keeps the current status.
    'message_patterns' => [
        'steadfast' => [
            '/assigned to (a )?rider|with (a )?rider|out for delivery|rider (has been )?assigned/i' => 'out_for_delivery',
            '/\bon hold\b|\bhold\b/i'                                                         => 'on_hold',
            '/\breturn(ing| to merchant|ed to hub)?\b/i'                                      => 'returning',
            '/received at|arrived at|reached/i'                                              => 'at_hub',
            '/sent to|dispatch(ed)?\b|in transit|on the way/i'                                => 'in_transit',
            '/picked up|pickup (completed|done)/i'                                           => 'picked_up',
            '/\bapproved\b/i'                                                                => 'approved',
        ],
    ],

    // Public tracking pages, verified against each courier's own site (Oct 2026). Steadfast no longer
    // has a public page by tracking code (its links are sender-issued tokens), so it has none here.
    'public_tracking_url' => [
        'redx'      => 'https://redx.com.bd/track-global-parcel/?trackingId=%s',
        'pathao'    => 'https://merchant.pathao.com/public-tracking?consignment_id=%s',
        'steadfast' => env('STEADFAST_PUBLIC_TRACK_URL_V2'),   // set only if Steadfast publishes a code-based URL again
    ],

    // Merchant portal entry pages (verified to load). Steadfast has no per-parcel URL to deep-link to.
    'portal_url' => [
        'steadfast' => 'https://steadfast.com.bd/login',
        'pathao'    => 'https://merchant.pathao.com/',
        'redx'      => 'https://redx.com.bd/',
    ],

    // Pathao's merchant panel expects this fixed value back when it tests the webhook (from Pathao's own plugin).
    'pathao_integration_header' => env('PATHAO_WEBHOOK_INTEGRATION_SECRET', 'f3992ecc-59da-4cbe-a049-a13da2018d51'),

    'stuck_days'             => (int) env('COURIER_STUCK_DAYS', 3),
    'refresh_cooldown'       => (int) env('COURIER_REFRESH_COOLDOWN', 120),   // seconds per parcel
    'payload_retention_days' => (int) env('COURIER_PAYLOAD_RETENTION_DAYS', 90),
    'sync_stale_minutes'     => 30,
    'http_timeout'           => 12,
];
