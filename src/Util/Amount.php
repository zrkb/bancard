<?php

declare(strict_types=1);

namespace Bancard\Util;

class Amount
{
    /**
     * Format an amount as a string with two decimals and "." as separator.
     *
     * Values are cast to float, so extra decimals are rounded.
     *
     * @param float|string $amount Amount to format
     * @return string Formatted amount, e.g. "10000.00"
     */
    public static function format(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Normalize an amount to Bancard's format, or return null if it is invalid.
     *
     * Ints and floats are formatted to two decimals. Numeric strings may have
     * at most two decimals and are padded without float conversion, so string
     * amounts are never silently rounded.
     *
     * @param mixed $amount Amount to normalize
     * @return string|null Normalized amount (e.g. "10000.00"), or null if the value is not a non-negative amount
     */
    public static function normalize(mixed $amount): ?string
    {
        if (is_int($amount)) {
            return $amount >= 0 ? $amount . '.00' : null;
        }

        if (is_float($amount)) {
            return is_finite($amount) && $amount >= 0 ? self::format($amount) : null;
        }

        if (is_string($amount) && preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches) === 1) {
            return $matches[1] . '.' . str_pad($matches[2] ?? '', 2, '0');
        }

        return null;
    }
}
