<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers in one comparable form. "+8801712345678", "8801712345678",
 * "008801712345678", "01712345678" and "1712345678" all become "01712345678".
 * Anything else keeps just its digits, so it can still be matched as typed.
 */
final class BdPhone
{
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '880')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        return $digits === '' ? null : substr($digits, 0, 20);
    }

    /** True for a complete mobile number (01XXXXXXXXX once normalized). */
    public static function isComplete(?string $phone): bool
    {
        return (bool) preg_match('/^01\d{9}$/', (string) self::normalize($phone));
    }
}
