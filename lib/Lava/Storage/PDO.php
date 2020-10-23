<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Storage {

    if (! extension_loaded('PDO')) {
        throw new \Exception ('PDO is required');
    }

    class PDO {

        private $ref,
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


        public function exec  ($query, $bind = NULL) {

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
        public function fetch_all (
            $query, $bind = NULL, $index = NULL
        )
        {
            $sth   = $this->_sth($query, $bind);

            if (! $sth) {
                return;
            }

            $sth->setFetchMode(\PDO::FETCH_ASSOC);

            $data  = $sth->fetchAll();

            if (! isset($index) || $index === '') {
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

        public function last_insert_id () {
            return $this->ref->lastInsertId();
        }


        public function factory ($target = NULL) {
            return new PDO\Factory ($this, $target);
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
                elseif (is_string($val)) {
                    $type = \PDO::PARAM_STR;
                }
                else   {
                    throw new \Exception (sprintf(
                        "Bad bind variable '%s'",
                                            gettype($val)
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
}

namespace Lava\Storage\PDO {

    class Factory {

        private $storage,
                $target   = [],
                $columns  = [],
                $join     = [],
                $filter,
                $group_by = [],
                $having,
                $order_by = [],
                $limit    = [];


        public function __construct (
            \Lava\Storage\PDO &$storage, $target = NULL
        )
        {
            $this->storage = $storage;
            $this->target  = $target instanceof self
                                ?       [$target]
                                : (array)$target;
            $this->filter  = new Factory\Filter ('and');
            $this->having  = new Factory\Filter ('and');
        }

        public function __call ($name, $args) {

            if (preg_match(
                '/^(filter|having)(?:_([a-zA-Z]\w*))?$/', $name, $match
            ))
            {
                $class  = $this->{$match[1]};
                $method = isset  ($match[2])
                    ?  $match[2]
                    : (is_callable(current($args)) ? 'and' : 'eq');

                if (   in_array     ($method, ['and', 'or', 'not'])
                    || method_exists($class, $method)
                )
                {
                    call_user_func_array([$class, $method], $args);
                    return $this;
                }
            }

            throw new \Exception (sprintf(
                'Call to undefined method %s::%s()',
                                   __CLASS__, $name
            ));
        }

        public function __invoke ($columns = NULL) {

            $query   = [];
            $bind    = [];

            $columns = $this->_expr(
                $columns ? $columns : $this->columns
            );

            $query[] = 'SELECT ' . (
                $columns['query'] ? $columns['query'] : '*'
            );
            $bind    = array_merge($bind, $columns['bind']);

            $target  = $this->_expr($this->target);

            if ($target['query']) {
                $query[] = 'FROM '          . $target['query'];
                $bind    = array_merge($bind, $target['bind']);
            }

            foreach ($this->join as $join) {
                $query[] =                    $join  ['query'];
                $bind    = array_merge($bind, $join  ['bind']);
            }

            $filter  = call_user_func($this->filter);

            if ($filter['query']) {
                $query[] = 'WHERE ' .         $filter['query'];
                $bind    = array_merge($bind, $filter['bind']);
            }

            if ($this->group_by) {
                $query[] = 'GROUP BY ' . join(', ', $this->group_by);
            }

            $having  = call_user_func($this->having);

            if ($having['query']) {
                $query[] = 'HAVING ' .        $having['query'];
                $bind    = array_merge($bind, $having['bind']);
            }

            if ($this->order_by) {
                $query[] = 'ORDER BY ' . join(', ', $this->order_by);
            }

            if ($this->limit) {
                $query[] = 'LIMIT '    . join(
                    ', ',  array_fill (1, count($this->limit), '?')
                );
                $bind    = array_merge($bind,   $this->limit);
            }

            return [
                'query' => join(' ', $query),
                'bind'  =>           $bind,
            ];
        }


        public function columns ($expr) {

            if ($expr instanceof self) {
                $expr = [$expr];
            }

            $this->columns = array_merge($this->columns, (array)$expr);

            return $this;
        }

        public function join (
            $target, $rel, $bind = NULL, $type = NULL
        )
        {
            $query   = [];

            if ($type) {
                $query[] = strtoupper($type);
            }

            $query[] = 'JOIN ' . $this->escape_key($target);

            $rel     = (array)$rel;
            $is_on   =        FALSE;

            foreach ($rel as $key => &$val) {

                if (is_string($key) || preg_match('/\W/', $val)) {
                    $is_on = TRUE;
                }

                $val = $this->escape_key($val);

                if (is_string($key)) {
                    $val   = $this->escape_key($key) . ' = ' . $val;
                }
            }

            $query[] = $is_on
                ? 'ON '     . join(' AND ', $rel)
                : 'USING (' . join(', ',    $rel) . ')';

            $this->join[] = [
                'query' => join(' ', $query),
                'bind'  => (array)   $bind,
            ];

            return $this;
        }
        public function left_join  ($target, $rel, $bind = NULL) {
            return $this->join($target, $rel, $bind, 'left');
        }
        public function right_join ($target, $rel, $bind = NULL) {
            return $this->join($target, $rel, $bind, 'right');
        }

        public function group_by ($expr) {
            foreach ((array)$expr as $val) {
                $this->group_by[] = $this->escape_key($val);
            }
            return $this;
        }

        public function order_by      ($expr) {
            foreach ((array)$expr as $val) {
                $this->order_by[] = $this->escape_key($val) . ' ASC';
            }
            return $this;
        }
        public function order_by_desc ($expr) {
            foreach ((array)$expr as $val) {
                $this->order_by[] = $this->escape_key($val) . ' DESC';
            }
            return $this;
        }

        public function limit ($count, $offset = 0) {

            $this->limit = [$count];

            if ($offset) {
                array_unshift($this->limit, $offset);
            }

            return $this;
        }


        public function add ($data) {

            $query = [
                'INSERT',
                'INTO ' . $this->escape_key(current($this->target)),
            ];
            $bind  = [];

            if   ($data instanceof self) {

                $columns = $this->_expr($this->columns);

                if ($columns['query']) {
                    $query[] = "(${columns['query']})";
                    $bind    = array_merge($bind, $columns['bind']);
                }

                $set     =              $data();
                $query[] =          $set['query'];
            }
            else {
                $set     = $this->_data($data);
                $query[] = 'SET ' . $set['query'];
            }

            $bind  = array_merge($bind, $set['bind']);

            return $this->storage->exec(join(' ', $query), $bind);
        }

        public function get ($index = NULL) {

            $select = $this();

            return $this->storage->fetch_all(
                $select['query'], $select['bind'], $index
            );
        }
        public function one () {

            $select = clone $this;
            $select-> limit  (1);
            $select = $select();

            return $this->storage->fetch(
                $select['query'], $select['bind']
            );
        }

        public function set ($data) {

            $query   = [
                'UPDATE ' . $this->escape_key(current($this->target)),
            ];
            $bind    = [];

            $set     = $this->_data($data);

            $query[] = 'SET ' .               $set   ['query'];
            $bind    = array_merge($bind,     $set   ['bind']);

            $filter  = call_user_func($this->filter);

            if ($filter['query']) {
                $query[] = 'WHERE ' .         $filter['query'];
                $bind    = array_merge($bind, $filter['bind']);
            }

            return $this->storage->exec(join(' ', $query), $bind);
        }

        public function del () {

            $query  = [
                'DELETE',
                'FROM ' . $this->escape_key(current($this->target)),
            ];
            $bind   = [];

            $filter = call_user_func($this->filter);

            if ($filter['query']) {
                $query[] = 'WHERE ' .         $filter['query'];
                $bind    = array_merge($bind, $filter['bind']);
            }

            return $this->storage->exec(join(' ', $query), $bind);
        }

        public function count ($key = '*') {
            return $this->_aggregate('COUNT', $key);
        }
        public function min   ($key) {
            return $this->_aggregate('MIN',   $key);
        }
        public function max   ($key) {
            return $this->_aggregate('MAX',   $key);
        }
        public function avg   ($key) {
            return $this->_aggregate('AVG',   $key);
        }
        public function sum   ($key) {
            return $this->_aggregate('SUM',   $key);
        }


        static
        public function escape_key ($key) {

            if (preg_match(
                '/^([^\W\d]\w*)(?:\.([^\W\d]\w*|\*))?$/',
                $key, $matches
            ))
            {
                array_shift($matches);

                foreach ($matches as &$match) {
                    if ($match != '*') {
                        $match = sprintf(
                            '`%s`',
                            str_replace('`', '``', $match)
                        );
                    }
                }
                $key = join('.', $matches);
            }

            return $key;
        }


        private function _expr ($data) {

            $query = [];
            $bind  = [];

            foreach ((array)$data as $alias => $expr) {

                if   ($expr instanceof self) {
                    $expr  = $expr();
                    $bind  = array_merge($bind, $expr['bind']);
                    $expr  = "(${expr['query']})";
                }
                else {
                    $expr  = $this->escape_key($expr);
                }

                if   (is_string($alias)) {
                    $expr .= ' AS ' . $this->escape_key($alias);
                }

                $query[] = $expr;
            }

            return [
                'query' => join(', ', $query),
                'bind'  =>            $bind,
            ];
        }

        private function _data ($data) {

            $query = [];
            $bind  = [];

            foreach ((array)$data as $key => $expr) {

                $key = $this->escape_key($key);

                if     (is_int($key)) {
                    $query[] = $expr;
                }
                elseif ($expr instanceof self) {
                    $expr    = $expr();
                    $query[] = "${key} = (${expr['query']})";
                    $bind    = array_merge($bind, $expr['bind']);
                }
                else   {
                    $query[] = "${key} = ?";
                    $bind [] = $expr;
                }
            }

            return [
                'query' => join(', ', $query),
                'bind'  =>            $bind,
            ];
        }

        private function _aggregate ($func, $key) {

            $select = $this(['val' => sprintf(
                "${func}(%s)", $this->escape_key($key)
            )]);
            $data   = $this->storage->fetch(
                $select['query'], $select['bind']
            );
            if (isset  ($data['val'])) {
                return +$data['val'];
            }
        }
    }
}

namespace Lava\Storage\PDO\Factory {

    class Filter {

        private $context,
                $stack = [];


        public function __construct ($context) {
            $this->context = $context;
        }

        public function __call ($name, $args) {

            if (in_array($name, ['and', 'or', 'not'])) {
                return $this->_context($name, current($args));
            }

            throw new \Exception (sprintf(
                'Call to undefined method %s::%s()',
                                   __CLASS__, $name
            ));
        }

        public function __invoke () {

            $query   = [];
            $bind    = [];

            foreach ($this->stack as $filter) {
                if   ($filter instanceof self) {

                    $filter = $filter();

                    if ($filter['query']) {
                        $query[] =                    $filter['query'];
                        $bind    = array_merge($bind, $filter['bind']);
                    }
                }
                else {

                    list   ($operator, $key, $val) = $filter;

                    if     ($operator == 'eq') {
                        $operator = isset($val) ?  '=' : 'is';
                    }
                    elseif ($operator == 'ne') {
                        $operator = isset($val) ? '!=' : 'is not';
                    }
                    elseif ($operator == 'lt') {
                        $operator = '<';
                    }
                    elseif ($operator == 'gt') {
                        $operator = '>';
                    }
                    elseif ($operator == 'lte') {
                        $operator = '<=';
                    }
                    elseif ($operator == 'gte') {
                        $operator = '>=';
                    }

                    $key = \Lava\Storage\PDO\Factory::escape_key($key);

                    if     ($operator == 'in' || $operator == 'not in') {
                        $val     = (array)$val;
                        $query[] = sprintf(
                            '%s %s (%s)',
                            $key,
                            strtoupper($operator),
                            join(', ', array_fill(1, count($val), '?'))
                        );
                        $bind    = array_merge($bind,        $val);
                    }
                    elseif ($operator == 'raw') {
                        $query[] = $key;
                        $bind    = array_merge($bind, (array)$val);
                    }
                    else   {
                        $query[] = $key . strtoupper(" ${operator} ?");
                        $bind [] = $val;
                    }
                }
            }

            $count   = count($query);
            $context = $this->context == 'not' ? 'and' : $this->context;
            $query   = join (strtoupper(" ${context} "), $query);

            if ($count > 1) {
                $query =    "(${query})";
            }
            if ($query && $this->context == 'not') {
                $query = "NOT ${query}";
            }

            return [
                'query' => $query,
                'bind'  => $bind,
            ];
        }


        public function eq  () {
            return $this->_operator('eq',       func_get_args());
        }
        public function ne  () {
            return $this->_operator('ne',       func_get_args());
        }
        public function lt  () {
            return $this->_operator('lt',       func_get_args());
        }
        public function gt  () {
            return $this->_operator('gt',       func_get_args());
        }
        public function lte () {
            return $this->_operator('lte',      func_get_args());
        }
        public function gte () {
            return $this->_operator('gte',      func_get_args());
        }

        public function like     () {
            return $this->_operator('like',     func_get_args());
        }
        public function not_like () {
            return $this->_operator('not like', func_get_args());
        }

        public function in     () {
            return $this->_operator('in',       func_get_args());
        }
        public function not_in () {
            return $this->_operator('not in',   func_get_args());
        }

        public function is_null     ($expr) {
            return $this->_operator('eq',       [$expr, NULL]);
        }
        public function is_not_null ($expr) {
            return $this->_operator('ne',       [$expr, NULL]);
        }

        public function raw ($expr, $bind = NULL) {

            if (is_null ($bind) && func_num_args() == 2) {
                $bind = [$bind];
            }

            return $this->_operator('raw',      [$expr, $bind]);
        }


        private function _context ($name, $arg) {

            $filter = new self ($name);

            if     (is_callable($arg)) {
                $arg($filter);
            }
            elseif (is_array   ($arg)) {
                foreach ($arg as $key => $val) {
                    $filter->eq ($key,   $val);
                }
            }

            $this->stack[] = $filter;

            return $this;
        }

        private function _operator ($name, $args) {

            if     (count($args) == 1) {
                $args   = (array) current ($args);
            }
            elseif (count($args) == 2) {
                list      ($key,   $val) = $args;
                $args   = [$key => $val];
            }

            if     (count($args) == 1) {
                $filter = [$name, key($args), current($args)];
            }
            else   {

                $filter = new self ('and');

                foreach ($args as $key => $val) {
                    $filter->_operator($name, [$key, $val]);
                }
            }

            $this->stack[] = $filter;

            return $this;
        }
    }
}

?>
