<?php

namespace Bancard\Util;

class Token
{
    /**
     * Make a new token.
     *
     * @return string
     */
    public static function make()
    {
        return md5(
            implode('', func_get_args())
        );
    }
}
