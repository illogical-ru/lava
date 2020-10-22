<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava {

    class Storage {

        static
        private $source = [];


        static
        public function source ($name = NULL, array $opts = NULL) {

            $source = NULL;
            $hash   = md5(serialize($opts));

            if (isset    (self::$source[$name])) {
                $source = self::$source[$name];
            }

            if ($opts && (! $source || $source['hash'] != $hash)) {

                if (! (isset($opts['driver']) && $opts['driver'])) {
                    if   ($name) {
                        $opts['driver'] = $name;
                    }
                    else {
                        throw new \Exception('No driver name specified');
                    }
                }

                $class  = __CLASS__ . '\\' . $opts['driver'];
                $source = [
                    'class' => new $class ($opts),
                    'hash'  =>     $hash,
                ];

                self::$source[$name] = $source;
            }

            if (! $source) {
                throw new \Exception("Can't find storage '${name}'");
            }

            return $source['class'];
        }
    }
}

?>
