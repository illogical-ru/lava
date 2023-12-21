<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Model;


class Collection extends \ArrayIterator {

    public
        $class,
        $limit,
        $count,
        $pages,
        $page;


    public function __construct ($data, $attr = []) {

        parent::__construct($data);

        foreach ($attr as $key => $val) {
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
    }


    public function as_array () {

        $data = [];

        foreach ($this as $key => $val) {
            $data[$key] = $val->as_array();
        }

        return $data;
    }
}

?>
