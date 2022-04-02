<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Model {

    protected static
        $storage = 0,
        $table,
        $id      = 'id',
        $columns = [],
        $limit   = 100;

    protected
        $index,
        $data    = [],
        $old     = [];


    public function __construct (array $data = []) {

        if     (!$data) {

            foreach ($this->columns() as $key) {
                $this->data[$key] = $this->column_default($key);
            }

            $this->old = $this->data;
        }
        elseif (!isset  ($data[$this->id()])) {
            throw new \Exception(sprintf(
                'No identifier specified for Model "%s"',
                $this->classname()
            ));
        }
        else   {
            $this->index = $data[$this->id()];
            $this->data  = $this->import($data);
        }
    }

    public function __get ($key) {
        if ( isset($this->data[$key])) {
            return $this->data[$key];
        }
    }
    public function __set ($key, $val) {

        if (!key_exists($key, $this->old)) {
            $this->old [$key] = isset($this->data[$key])
                                    ? $this->data[$key]
                                    : NULL;
        }

        $this->data[$key] = $val;
    }


    public static function classname () {
        return get_called_class();
    }

    public static function storage () {

        $class = self::classname();

        return \Lava::storage($class::$storage);
    }

    public static function table () {

        $class = self::classname();

        return $class::$table
            ?  $class::$table
            :  self::_camel2snake(preg_replace('/.*\\\/', '', $class));
    }

    public static function id () {

        $class = self::classname();

        return (string)$class::$id;
    }

    public static function columns () {

        $class = self::classname();

        return array_keys($class::$columns);
    }

    public static function column_default     ($key) {

        $class  = self::classname();
        $method = __FUNCTION__ . '_' . $key;

        if (method_exists($class, $method)) {
            return $class::$method();
        }
        if (isset ($class::$columns[$key]['default'])) {
            return $class::$columns[$key]['default'];
        }
    }
    public static function column_is_unique   ($key) {

        $class = self::classname();

        return isset($class::$columns[$key]['unique'])
                ?    $class::$columns[$key]['unique']
                :    $class::id() ==  $key;
    }
    public static function column_is_not_null ($key) {

        $class = self::classname();

        if (isset ($class::$columns[$key]['not_null'])) {
            return $class::$columns[$key]['not_null'];
        }
    }
    public static function column_is_valid    ($key, $val) {

        $class = self::classname();
        $error = NULL;

        if     (is_null($val)) {
            if ( $class::column_is_not_null($key)) {
                $error = 'null';
            }
        }
        elseif (isset($class::$columns[$key]['valid'])) {

            $validator = new Validator (
                $class::$columns[$key]['valid']
            );

            if (!$validator->test($val)) {
                $error = 'invalid';
            }
        }

        return $error;
    }

    public static function limit () {

        $class = self::classname();

        return (int)$class::$limit;
    }

    public static function valid ($data) {

        $class  = self::classname();
        $errors = NULL;

        foreach ($data as $key => $val) {

            $error = $class::column_is_valid($key, $val);

            if ($error) {
                $errors[$key] = $error;
            }
        }

        return $errors;
    }

    public static function import ($data) {
        return $data;
    }
    public static function export ($data) {
        return $data;
    }

    public function as_array () {
        return $this->data;
    }

    public function has_one  ($class, $fk = NULL) {

        if (!$fk) {
            $fk = self::_fk($class);
        }
        if ( isset($this->data[$fk])) {
            return $class::one($this->data[$fk]);
        }
    }
    public function has_many ($class, $fk = NULL) {

        if (!$fk) {
            $fk = self::_fk($this::classname());
        }

        return $class::find([$fk => $this->id]);
    }


    protected static function _fk ($class) {
        return (
              strtolower(preg_replace('|.*\\\|', '', $class))
            . '_'
            . $class::id()
        );
    }

    protected static function _camel2snake ($val) {
        return strtolower(preg_replace('/\B(?=[A-Z])/', '_', $val));
    }
}

?>
