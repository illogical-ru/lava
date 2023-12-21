<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Storage\PDO;

use Lava\Storage\PDO;
use Lava\Storage\PDO\Factory\Filter;


class Factory {

    private
        $storage,
        $target   = [],
        $columns  = [],
        $join     = [],
        $filter,
        $group_by = [],
        $having,
        $order_by = [],
        $limit    = [];


    public function __construct (PDO &$storage, $target = NULL) {

        $this->storage = $storage;
        $this->target  = $target instanceof self
                            ?       [$target]
                            : (array)$target;
        $this->filter  = new Filter ('and');
        $this->having  = new Filter ('and');
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

    public function join ($target, $rel, $bind = NULL, $type = NULL) {

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
    public function left_join ($target, $rel, $bind = NULL) {
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

    public function order_by ($expr) {
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

        if   ($count) {

            $this->limit = [$count];

            if ($offset) {
                array_unshift($this->limit, $offset);
            }
        }
        else {
            $this->limit = [];
        }

        return $this;
    }


    public function add ($data, $update = NULL) {

        $query = [
            'INSERT',
            'INTO ' . $this->escape_key(current($this->target)),
        ];
        $bind  = [];

        if   ($data instanceof self) {

            $columns = $this->_expr($this->columns);

            if ($columns['query']) {
                $query[] = "({$columns['query']})";
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

        if   ($update) {
            $update  = $this->_data($update);
            $query[] = 'ON DUPLICATE KEY';
            $query[] = 'UPDATE ' .        $update['query'];
            $bind    = array_merge($bind, $update['bind']);
        }

        return $this->storage->exec(join(' ', $query), $bind);
    }

    public function one () {

        $select = clone $this;
        $select-> limit  (1);
        $select = $select();

        return $this->storage->fetch(
            $select['query'], $select['bind']
        );
    }
    public function get ($index = NULL) {

        $select = $this();

        return $this->storage->fetch_all(
            $select['query'], $select['bind'], $index
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

    public function count ($key = NULL) {
        return $this->_aggregate('COUNT', $key ? $key : '*');
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


    public function hash () {
        return md5(serialize($this()));
    }


    public static function escape_key ($key) {

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
                $expr  = "({$expr['query']})";
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
                $query[] = "{$key} = ({$expr['query']})";
                $bind    = array_merge($bind, $expr['bind']);
            }
            else   {
                $query[] = "{$key} = ?";
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
            "{$func}(%s)", $this->escape_key($key)
        )]);
        $data   = $this->storage->fetch(
            $select['query'], $select['bind']
        );
        if (isset  ($data['val'])) {
            return +$data['val'];
        }
    }
}

?>
