<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Stash {

    private $data = [];


    public function __construct () {

        $args = func_num_args( ) == 1
            ?   func_get_arg (0)
            :   func_get_args( );

        foreach ((array)$args as $key => $val) {
            self::__set($key, $val);
        }
    }


    public function __get ($key) {

        $data = &$this->data;

        if (isset($data[$key])) {
            return is_array($data[$key])
                ?  end     ($data[$key])
                :           $data[$key];
        }
    }
    public function __set ($key, $val) {
        return $this->data[$key] = $val;
    }

    public function __call ($key, $args) {

        $data = &$this->data;

        if     ($args) {
            return self::__set($key, $args);
        }
        elseif (isset    ($data[$key])) {
            return (array)$data[$key];
        }
        else   {
            return [];
        }
    }

    public function __isset ($key) {
        return isset($this->data[$key]);
    }
    public function __unset ($key) {
               unset($this->data[$key]);
    }

    public function _has ($key) {
        return key_exists($key, $this->data);
    }

    public function _data () {
        return $this->data;
    }
}

?>
