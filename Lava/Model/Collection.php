<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Model;


class Collection implements \Iterator {

    private
        $data,
        $attr;


    public function __construct (array $data, $attr = []) {
        $this->data = $data;
        $this->attr = $attr;
    }


    public function current () {
        return current($this->data);
    }
    public function key     () {
        return key    ($this->data);
    }
    public function next    () {
        next          ($this->data);
    }
    public function rewind  () {
        reset         ($this->data);
    }
    public function valid   () {
        return key    ($this->data);
    }

    public function as_array () {

        $data = [];

        foreach ($this->data as $val) {
            $data[] = $val->as_array();
        }

        return $data;
    }
}

?>
