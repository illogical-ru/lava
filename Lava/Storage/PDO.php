<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Storage;

use Lava\Storage\PDO\Factory;


if (!extension_loaded    ('PDO')) {
    throw new \Exception ('PDO is required');
}

class PDO {

    private
        $ref,
        $opts,
        $error;


    public function __construct ($opts) {

        if (is_string($opts)) {
            $opts = ['dsn' => $opts];
        }

        $attr = isset($opts['attr']) ? $opts['attr'] : NULL;

        if (isset($opts['persistent'])) {
            $attr[\PDO::ATTR_PERSISTENT] = $opts['persistent'];
        }
        if (isset($opts['timeout'])) {
            $attr[\PDO::ATTR_TIMEOUT]    = $opts['timeout'];
        }

        $this->ref  = new \PDO (
            isset($opts['dsn'])      ? $opts['dsn']      : NULL,
            isset($opts['username']) ? $opts['username'] : NULL,
            isset($opts['password']) ? $opts['password'] : NULL,
            $attr
        );
        $this->opts = $opts;

        if (isset($opts['charset']) && $opts['charset']) {
            $this->ref->exec("SET NAMES $opts[charset]");
        }
    }
    public function __destruct () {
        $this->ref = NULL;
    }


    public function error () {
        return $this->error;
    }

    public function exec ($query, $bind = NULL) {

        $sth = $this->_sth($query, $bind);

        if ($sth) {
            return $sth->rowCount();
        }
    }

    public function fetch ($query, $bind = NULL) {

        $sth = $this->_sth($query, $bind);

        if ($sth) {
            return $sth->fetch(\PDO::FETCH_ASSOC);
        }
    }
    public function fetch_all ($query, $bind = NULL, $index = NULL) {

        $sth   = $this->_sth($query, $bind);

        if (!$sth) {
            return;
        }

        $sth->setFetchMode(\PDO::FETCH_ASSOC);

        $data  = $sth->fetchAll();

        if (is_null($index) || $index === '') {
            return $data;
        }

        $index = (array)$index;
        $assoc = [];

        foreach ($data as $item) {

            $keys = [];

            foreach ($index as $key) {
                $keys[] = isset($item[$key])
                            ?   $item[$key]
                            :   NULL;
            }

            $assoc[join(',', $keys)] = $item;
        }

        return $assoc;
    }

    public function begin () {
        return $this->ref->beginTransaction();
    }
    public function commit () {
        return $this->ref->commit();
    }
    public function rollback () {
        return $this->ref->rollBack();
    }

    public function last_insert_id () {
        return $this->ref->lastInsertId();
    }


    public function factory ($target = NULL) {
        return new  Factory ($this, $target);
    }


    private function _sth ($query, $bind = NULL) {

        $this->error = NULL;

        $sth = $this->ref->prepare($query);

        foreach ((array)$bind as $key => $val) {

            $key =  is_int ($key) ? $key + 1 : ":${key}";

            if     (is_null  ($val)) {
                $type = \PDO::PARAM_NULL;
            }
            elseif (is_bool  ($val)) {
                $type = \PDO::PARAM_BOOL;
            }
            elseif (is_int   ($val)) {
                $type = \PDO::PARAM_INT;
            }
            elseif (is_string($val)
                ||  is_float ($val)
            )
            {
                $type = \PDO::PARAM_STR;
            }
            else   {
                throw new \Exception (sprintf(
                    "Bad bind variable '%s'", gettype($val)
                ));
            }

            $sth->bindValue($key, $val, $type);
        }

        if   ($sth->execute()) {
            return $sth;
        }
        else {
            list(,, $this->error) = $sth->errorInfo();
        }
    }
}

?>
