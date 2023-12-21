<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Storage\PDO\Factory;

use Lava\Storage\PDO\Factory;


class Filter {

    private
        $context,
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

                $filter  = $filter();

                if     ($filter['query']) {
                    $query[]  =                    $filter['query'];
                    $bind     = array_merge($bind, $filter['bind']);
                }
            }
            else {

                list   ($operator, $key, $val) =  $filter;

                if     ($operator == 'raw') {
                    $query[]  =                           $key;
                    $bind     = array_merge($bind, (array)$val);
                    continue;
                }

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

                if     ($val instanceof Factory) {
                    $val      = $val();
                    $expr     = "({$val['query']})";
                    $val      = $val['bind'];
                }
                elseif ($operator == 'in' || $operator == 'not in') {
                    $val      = (array)$val;
                    $expr     = (
                          '('
                        . join(', ', array_fill(1, count($val), '?'))
                        . ')'
                    );
                }
                elseif ($operator == 'between') {
                    $expr     = '? AND ?';
                }
                else   {
                    $val      = [$val];
                    $expr     = '?';
                }

                $query[] = join(' ', [
                    Factory::escape_key($key),
                    strtoupper($operator),
                    $expr,
                ]);
                $bind    = array_merge($bind, $val);
            }
        }

        $count   = count($query);
        $context = $this->context == 'not' ? 'and' : $this->context;
        $query   = join (strtoupper(" {$context} "), $query);

        if ($count > 1) {
            $query =    "({$query})";
        }
        if ($query && $this->context == 'not') {
            $query = "NOT {$query}";
        }

        return [
            'query' => $query,
            'bind'  => $bind,
        ];
    }


    public function eq  ($key, $val) {
        return $this->_operator('eq',       [$key, $val]);
    }
    public function ne  ($key, $val) {
        return $this->_operator('ne',       [$key, $val]);
    }
    public function lt  ($key, $val) {
        return $this->_operator('lt',       [$key, $val]);
    }
    public function gt  ($key, $val) {
        return $this->_operator('gt',       [$key, $val]);
    }
    public function lte ($key, $val) {
        return $this->_operator('lte',      [$key, $val]);
    }
    public function gte ($key, $val) {
        return $this->_operator('gte',      [$key, $val]);
    }

    public function like     ($key, $val) {
        return $this->_operator('like',     [$key, $val]);
    }
    public function not_like ($key, $val) {
        return $this->_operator('not like', [$key, $val]);
    }

    public function in     ($key, $val) {
        return $this->_operator('in',       [$key, $val]);
    }
    public function not_in ($key, $val) {
        return $this->_operator('not in',   [$key, $val]);
    }

    public function between ($key, array $val) {
        return $this->_operator('between',  [
            $key, [array_shift($val), array_shift($val)]
        ]);
    }

    public function is_null     ($key) {
        return $this->_operator('eq',       [$key, NULL]);
    }
    public function is_not_null ($key) {
        return $this->_operator('ne',       [$key, NULL]);
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

?>
