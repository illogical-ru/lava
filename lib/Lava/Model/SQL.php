<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Model;

use Lava\Model;
use Lava\Model\SQL\ResultSet;


class SQL extends Model {

    public static function one ($id) {

        $class = self::classname();

        $item  = $class::storage()
            ->factory($class::table())
            ->filter ($class::id(), $id)
            ->one    ();

        if ($item) {
            return new $class ($item);
        }
    }

    public static function find ($filter = NULL) {

        $rs = new ResultSet (self::classname());

        return $rs->filter($filter);
    }

    public function save () {

        if (!$this->old) {
            return FALSE;
        }

        $storage = $this->storage();

        $data    = [];
        $unique  = [];

        foreach (array_keys($this->old) as $key) {

            $data[$key] = $this->data[$key];

            if (   isset              ($data[$key])
                && $this->column_is_unique  ($key)
                && $this->old[$key] != $data[$key]
            )
            {
                $unique[] = $key;
            }
        }

        $errors  = $this->valid($data);

        foreach ($unique as $key) {
            if (   !isset($errors[$key])
                && $storage
                    ->factory($this->table())
                    ->filter ($key, $data[$key])
                    ->count  ()
            )
            {
                $errors[$key] = 'exist';
            }
        }

        if (!$errors) {

            $data    = $this->export($this->data);

            foreach (array_keys($data) as $key) {
                if   (key_exists($key, $this->old)) {
                    unset($this->old[$key]);
                }
                else {
                    unset($data     [$key]);
                }
            }

            $factory = $storage->factory($this->table());

            if     ( $this->index) {
                $count = $factory
                    ->filter($this->id(), $this->index)
                    ->set   ($data);
            }
            else   {
                $count = $factory->add($data);
            }

            if     (!$count) {

                $error = $storage->error();

                if ($error) {
                    $errors[] = $error;
                }
            }
            elseif ( isset ($data[$this->id()])) {
                $this->index = $data[$this->id()];
            }
            elseif (!$this->index) {
                $this->index = $storage->last_insert_id();
                $this->data[$this->id()] = $this->index;
            }
        }

        return $errors;
    }

    public function del () {

        if   (!$this->index) {
            return FALSE;
        }

        $count = $this
            ->storage()
            ->factory($this->table())
            ->filter ($this->id(), $this->index)
            ->del    ();

        if   ( $count) {

            $this->index = NULL;
            $this->old   = $this->data;

            return TRUE;
        }
        else {
            return FALSE;
        }
    }
}

?>
