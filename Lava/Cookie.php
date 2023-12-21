<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;

use Lava\Date;


class Cookie {

    public function __get           ($key) {
        if (isset          ($_COOKIE[$key])) {
            return is_array($_COOKIE[$key])
                ?  end     ($_COOKIE[$key])
                :           $_COOKIE[$key];
        }
    }
    public function __set ($key, $val) {

        $data = self::_normalize($key, $val);
        $opts = array_slice(func_get_args(), 2);

        // expire
        if (isset($opts[0])) {
            $opts[0] = Date::time_offset($opts[0]);
        }

        $done = 0;

        foreach ($data as $item) {
            $done   += call_user_func_array(
                'setcookie', array_merge($item, $opts)
            );
        }

        return $done;
    }

    public function __call ($key, $args) {
        if     ($args) {
            return call_user_func_array(
                [$this, '__set'], array_merge([$key], $args)
            );
        }
        elseif (isset    ($_COOKIE[$key])) {
            return (array)$_COOKIE[$key];
        }
        else   {
            return [];
        }
    }

    public function __isset ($key) {
        return isset   ($_COOKIE[$key]);
    }
    public function __unset ($key) {

        if   (!isset   ($_COOKIE[$key])) {
            return;
        }

        if   ( is_array($_COOKIE[$key])) {

            $data = $_COOKIE[$key];

            array_walk_recursive(
                $data, function(&$item) {$item = NULL;}
            );
        }
        else {
            $data = NULL;
        }

        self::$key($data);
    }

    public function _data () {
        return $_COOKIE;
    }


    private static function _normalize ($key, $val) {
        if   (is_array($val)) {

            $data = [];

            foreach ($val as $index => $item) {
                $data = array_merge(
                    $data,
                    self::_normalize("{$key}[{$index}]", $item)
                );
            }

            return   $data;
        }
        else {
            return [[$key, $val]];
        }
    }
}

?>
