<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Storage {

    private static $source = [];


    public static function source ($name = NULL, $opts = NULL) {

        if (      $opts) {
            ksort($opts);
        }

        $source = NULL;
        $hash   = md5(serialize($opts));

        if (isset    (self::$source[$name])) {
            $source = self::$source[$name];
        }

        if ($opts && (!$source || $source['hash'] != $hash)) {

            if (!(isset($opts['driver']) && $opts['driver'])) {
                if   ($name) {
                    $opts['driver'] = $name;
                }
                else {
                    throw new \Exception('No driver name specified');
                }
            }

            $class  = __CLASS__ . '\\' . $opts['driver'];
            $source = [
                'obj'  => new $class ($opts),
                'hash' =>     $hash,
            ];

            self::$source[$name] = $source;
        }

        if (!$source) {
            throw new \Exception("Can't find storage '${name}'");
        }

        return $source['obj'];
    }
}

?>
