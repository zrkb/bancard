<?php

declare(strict_types=1);

namespace Bancard\Util;

class Amount
{
    public static function format(float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
