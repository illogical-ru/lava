<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Session {

    public function __get            ($key) {
        if (isset          ($_SESSION[$key])) {
            return is_array($_SESSION[$key])
                ?  end     ($_SESSION[$key])
                :           $_SESSION[$key];
        }
    }
    public function __set ($key,   $val) {
        return $_SESSION  [$key] = $val;
    }

    public function __call ($key, $args) {
        if     ($args) {
            return $this->__set($key, $args);
        }
        elseif (isset    ($_SESSION[$key])) {
            return (array)$_SESSION[$key];
        }
        else   {
            return [];
        }
    }

    public function __isset ($key) {
        return isset($_SESSION[$key]);
    }
    public function __unset ($key) {
               unset($_SESSION[$key]);
    }

    public function _data () {
        return isset($_SESSION) ? $_SESSION : [];
    }
}

?>
