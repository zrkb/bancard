<?php

namespace Bancard\Util;

class Token
{
    /**
     * Creates a single-use token that can be used with any API method.
     *
     * @return string
     */
    public static function make(): string
    {
        return hash(
            'md5',
            implode('', func_get_args())
        );
    }
}
