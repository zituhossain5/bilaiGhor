<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** Phone input and Bangla formatting for the Manual Fraud Check page. */
final class FraudPhone
{
    private const BN_DIGITS = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
        '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];

    private const BN_MONTHS = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
        'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

    /**
     * "01XXXXXXXXX" from 01…, +8801…, 8801…, 008801…, with spaces, dashes, dots, brackets or Bangla
     * digits. Null when it isn't a valid Bangladeshi mobile number, so no API call is made for it.
     */
    public static function normalize(?string $input): ?string
    {
        $text = strtr(trim((string) $input), self::BN_DIGITS);
        if ($text === '' || preg_match('/[^0-9\s\-+().]/u', $text)) {
            return null;
        }

        $phone = (string) BdPhone::normalize($text);

        return preg_match('/^01[3-9]\d{8}$/', $phone) ? $phone : null;
    }

    /** The ways a normalized number may be stored on orders. */
    public static function variants(string $phone): array
    {
        return [$phone, '88' . $phone, '+88' . $phone];
    }

    /** 019****0165 */
    public static function mask(string $phone): string
    {
        return substr($phone, 0, 3) . str_repeat('*', max(strlen($phone) - 7, 0)) . substr($phone, -4);
    }

    public static function banglaDigits(string|int|float $value): string
    {
        return strtr((string) $value, array_flip(self::BN_DIGITS));
    }

    /** ৫ জানুয়ারি ২০২৬ */
    public static function banglaDate(CarbonInterface $date): string
    {
        return self::banglaDigits($date->day) . ' ' . self::BN_MONTHS[$date->month - 1] . ' ' . self::banglaDigits($date->year);
    }
}
