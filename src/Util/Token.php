<?php

declare(strict_types=1);

namespace Bancard\Util;

class Token
{
    public static function make(string ...$parts): string
    {
        return hash('md5', implode('', $parts));
    }
}
