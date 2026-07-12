<?php

return [
    // ৳ of eligible spending that earns 1 reward point (floor division).
    'earn_amount_per_point' => 100,

    // ৳ discount value of 1 reward point at redemption.
    'point_value' => 1,

    // Order statuses (order_statuses.id) that trigger reward actions.
    'award_status'  => 6,   // Completed — points are credited here
    'cancel_status' => 11,  // Cancelled — spent points restored / earned points reversed
];
