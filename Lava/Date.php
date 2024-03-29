<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Date {

    private static $offset = [
        's' => 1,
        'm' => 60,
        'h' => 3600,
        'D' => 86400,
        'W' => 604800,   //   7D
        'M' => 2592000,  //  30D
        'Y' => 31536000, // 365D
    ];


    public static function time_offset ($offset) {

        if (   preg_match('/^([-+]?\d+)(\D)$/', $offset, $match)
            && isset     (self::$offset[$match[2]])
        )
        {
            $offset = $match[1] * self::$offset[$match[2]];
        }

        return time() + $offset;
    }
}

?>
