<?php

declare(strict_types=1);

namespace Bancard\Response;

/**
 * Type-safe readers for decoded JSON values.
 */
trait JsonValues
{
    /**
     * Convert a decoded value to string when it is a string or number.
     *
     * Bancard may send IDs and codes as JSON numbers; this avoids TypeErrors
     * in getters declared as ?string.
     *
     * @param mixed $value Decoded response value
     * @return string|null String value, or null when missing or not a string/number
     */
    protected static function stringValue(mixed $value): ?string
    {
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : null;
    }

    /**
     * Return the value when it is a decoded JSON object.
     *
     * @param mixed $value Decoded response value
     * @return \stdClass|null The object, or null when missing or not an object
     */
    protected static function objectValue(mixed $value): ?\stdClass
    {
        return $value instanceof \stdClass ? $value : null;
    }
}
