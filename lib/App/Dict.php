<?php

namespace App;


class Dict {

    private $data;


    public function __construct ($file) {
        $this->data = include    $file;
    }


    public function tr ($args, $prefix = NULL) {

        $is_array = is_array($args);
        $args     =   (array)$args;

        foreach ($args as $key => &$val) {

            $keys   = [];

            if (isset    ($prefix)) {
                $keys[] = $prefix;
            }
            if (is_string($key)) {
                $keys[] = $key;
            }

            $keys[] = $val;

            while ($keys) {

                $key = strtolower(preg_replace(
                    '/\s+/', '_',  join('_', $keys)
                ));

                array_shift($keys);

                if (! isset($this->data[$key])) {
                    continue;
                }

                $val = $this->data[$key];

                if (  is_array    ($val)) {
                    $val = current($val);
                }

                break;
            }
        }

        return $is_array ? $args : array_shift($args);
    }

    public function ls ($key) {
        return isset($this->data[$key])
            ? (array)$this->data[$key]
            : [];
    }

    public function has_key ($key) {
        return isset($this->data[$key]);
    }
}

?>
