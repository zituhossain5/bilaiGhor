<?php

namespace App\Support;

use App\Models\User;
use App\Models\Order;

final class OrderPurpose
{
    public const SALE = 'sale';
    public const PROMOTIONAL_GIVEAWAY = 'promotional_giveaway';
    public const FREE_SAMPLE = 'free_sample';
    public const CUSTOMER_GIFT = 'customer_gift';
    public const CUSTOMER_REPLACEMENT = 'customer_replacement';

    public const LABELS = [
        self::SALE => 'Normal Sale',
        self::PROMOTIONAL_GIVEAWAY => 'Promotional Giveaway',
        self::FREE_SAMPLE => 'Free Sample',
        self::CUSTOMER_GIFT => 'Customer Gift',
        self::CUSTOMER_REPLACEMENT => 'Customer Replacement',
    ];

    public const PROMOTIONAL = [
        self::PROMOTIONAL_GIVEAWAY,
        self::FREE_SAMPLE,
        self::CUSTOMER_GIFT,
    ];

    public static function label(?string $purpose): string
    {
        return self::LABELS[$purpose ?: self::SALE] ?? self::LABELS[self::SALE];
    }

    public static function isNonSale(?string $purpose): bool
    {
        return in_array($purpose, [...self::PROMOTIONAL, self::CUSTOMER_REPLACEMENT], true);
    }

    public static function canClassify(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roles = $user->getRoleNames()->map(fn ($role) => strtolower($role));

        return $user->id == 1 || $roles->contains('admin') || $roles->contains('accountant') || $user->can('fund-list');
    }
}
