<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava\Model\SQL;

use Lava\Model\Collection;


class ResultSet {

    private
        $class,
        $factory;


    public function __construct ($class) {
        $this->class   = $class;
        $this->factory = $class::storage()->factory($class::table());
    }


    public function one () {

        $item = $this->factory->one();

        if ($item) {
            return new $this->class ($item);
        }
    }

    public function collect ($limit = 0, $page = 1) {

        $class = $this->class;
        $count = 0;
        $pages = 1;

        if ( func_num_args()) {

            if     ($limit < 1) {
                $limit = 1;
            }
            elseif ($limit > $class::limit()) {
                $limit = $class::limit();
            }
            else   {
                $limit = (int)$limit;
            }

            $count = $this->count();
            $pages = (int)  ceil ($count / $limit);

            if     ($page < 1) {
                $page  = 1;
            }
            elseif ($page > $pages) {
                $page  = $pages;
            }
            else   {
                $page  = (int)$page;
            }

            $this->factory->limit(
                $limit, ($page - 1) * $limit
            );
        }

        $data  = $this->factory->get($class::id());

        if (!$data) {
            return;
        }

        foreach ($data   as    &$item) {
            $item = new $class ($item);
        }

        return new Collection ($data, [
            'limit' => $limit,
            'count' => $count ? $count : count($data),
            'pages' => $pages,
            'page'  => $page,
        ]);
    }

    public function set ($data) {

        $class = $this->class;

        return $this->factory->set($class::export($data));
    }

    public function del () {
        return $this->factory->del();
    }

    public function filter ($data) {

        if     (is_string  ($data)) {
            $this->factory->filter_raw($data);
        }
        elseif (is_array   ($data)) {
            foreach ($data as $key => $val) {
                $this->factory->filter($key, $val);
            }
        }
        elseif (is_callable($data)) {
            $this->factory->filter    ($data);
        }

        return $this;
    }

    public function count ($key = NULL) {
        return $this->factory->count($key);
    }
    public function min   ($key) {
        return $this->factory->min  ($key);
    }
    public function max   ($key) {
        return $this->factory->max  ($key);
    }
    public function avg   ($key) {
        return $this->factory->avg  ($key);
    }
    public function sum   ($key) {
        return $this->factory->sum  ($key);
    }
}

?>
