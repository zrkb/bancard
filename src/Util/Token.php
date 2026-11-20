<?php

declare(strict_types=1);

namespace Bancard\Util;

class Token
{
    /**
     * Generate an MD5 token from the concatenated parts.
     *
     * @param string ...$parts Token parts, in order
     * @return string MD5 hash
     */
    public static function make(string ...$parts): string
    {
        return hash('md5', implode('', $parts));
    }
}
