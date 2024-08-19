<?php

/*!
 * https://github.com/illogical-ru/lava
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

        $this->errors = [];

        if (!$this->old) {
            return;
        }

        $id_key  = $this->id();
        $data    = [];
        $unique  = [];

        foreach (array_keys($this->old) as $key) {

            $data[$key] = $this->data[$key];

            if (   isset               ($data[$key])
                && $this->column_is_unique   ($key)
                && $this->old[$key] !== $data[$key]
            )
            {
                $unique[] = $key;
            }
        }

        $storage = $this->storage();
        $errors  = $this->has_errors($data);

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

            $factory = $storage->factory($this->table());
            $data    = $this->export($data);

            if     ( $this->index) {
                $count = $factory
                    ->filter($id_key, $this->index)
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
            elseif ( isset    ($data[$id_key])) {
                $this->index = $data[$id_key];
            }
            elseif (!$this->index) {
                $this->index = $storage->last_insert_id();
                $this->data[$id_key] = $this->index;
            }

            if     ( $count) {
                $this->old   = [];
            }
        }

        $this->errors = $errors;

        return empty($errors);
    }

    public function del () {

        $this->errors = [];

        if   (!$this->index) {
            return FALSE;
        }

        $storage = $this->storage();

        $count   = $storage
            ->factory($this->table())
            ->filter ($this->id(), $this->index)
            ->del    ();

        if   ( $count) {

            foreach ($this as  $key => $val) {
                $this->$key = is_array($val) ? [] : NULL;
            }

            return TRUE;
        }
        else {

            $error = $storage->error();

            if ($error) {
                $this->errors[] = $error;
            }

            return FALSE;
        }
    }
}

?>
