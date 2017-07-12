<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class SQLBuilder {

	private	$sql  = array(),
		$bind = array();


	public function __call ($cmd, $args) {

		$data = NULL;

		if     (isset($args[0]))
			$data = join(', ', (array)$args[0]);
		if     (isset($args[1]) && $args[1])
			$data = "(${data})";
		elseif (isset($data))
			$data = " ${data}";

		$this->sql[]  = strtoupper($cmd) . $data;

		return $this;
	}

	public function __toString () {
		return join(' ', $this->sql);
	}
	public function __invoke () {
		return $this->bind;
	}


	public function select ($expr = '*', $opts = NULL) {

		$expr = (array) $expr;

		foreach ($expr as $key => &$val)
			$val = $this->_item(array($key => $val));

		$this->sql[] =	  'SELECT' . $this->_opts($opts)
				. ' '      . join(', ',   $expr);
		return $this;
	}

	public function insert ($opts = NULL) {
		$this->sql[] =	  'INSERT' . $this->_opts($opts);
		return $this;
	}

	public function update ($table, $opts = NULL) {
		$this->sql[] =	  'UPDATE' . $this->_opts      ($opts)
				. ' '      . $this->_escape_key($table);
		return $this;
	}

	public function delete ($opts = NULL) {
		$this->sql[] =	  'DELETE' . $this->_opts($opts);
		return $this;
	}

	public function from ($src) {

		if (! is_array($src)) $src = array($src);

		foreach ($src as $key => &$val)
			$val = $this->_item(array($key => $val));

		$this->sql[] = 'FROM ' . join(', ',  $src);

		return $this;
	}
	public function into ($dst) {
		$this->sql[] = 'INTO ' . $this->_escape_key($dst);
		return $this;
	}

	public function where  ($cond) {
		$this->sql[] = 'WHERE';
		$this->_cond   ($cond);
		return $this;
	}
	public function having ($cond) {
		$this->sql[] = 'HAVING';
		$this->_cond   ($cond);
		return $this;
	}

	public function group_by ($cols) {
		$this->sql[] = 'GROUP BY ' . $this->_by($cols);
		return $this;
	}
	public function order_by ($cols) {
		$this->sql[] = 'ORDER BY ' . $this->_by($cols);
		return $this;
	}

	public function join       ($src, $cond, $mode = NULL) {

		if ($mode) $this->sql[] = $mode;

		$this->sql[] = 'JOIN ' . $this->_item((array)$src) . ' ON';
		$this->_cond($cond);

		return $this;
	}
	public function left_join  ($src, $cond) {
		return $this->join($src, $cond, 'LEFT');
	}
	public function right_join ($src, $cond) {
		return $this->join($src, $cond, 'RIGHT');
	}

	public function union (array $select, $all = FALSE) {

		$union = array();

		foreach ($select as $sql)
			if ($this->_is_self($sql))
				$union[] = $this->_val($sql);

		$this->sql[] = join(
			$all ? ' UNION ALL ' : ' UNION ', $union
		);

		return $this;
	}

	public function set ($data) {
		$this->sql[] = 'SET ' . $this->_data($data);
		return $this;
	}

	public function on_duplicate_key_update ($data) {
		$this->sql[] =	  'ON DUPLICATE KEY UPDATE '
				. $this->_data  ($data);
		return $this;
	}

	public function values ($data) {

		$keys    = array_keys((array)$data);
		$has_key = NULL;

		$values  = array();

		foreach (func_get_args() as $i => $arg) {

			$row      = array();

			foreach ($keys as $key) {

				if (! $i && is_string($key)) $has_key = TRUE;

				$row[] = $this->_val(
					isset($arg[$key]) ? $arg[$key] : NULL
				);
			}

			$values[] = '(' . join(', ', $row) . ')';
		}

		$values  = 'VALUES '    . join(', ', $values);

		if ($has_key) {

			$row      = array();

			foreach ($keys as $key)
				$row[] = $this->_escape_key($key);

			$values   = '(' . join(', ', $row) . ') ' . $values;
		}

		$this->sql[] = $values;

		return $this;
	}


	private function _is_self ($arg) {
		return $arg instanceof $this;
	}

	public function _escape_key ($key, $quote = TRUE) {
		if   (preg_match('/^[^\W\d]\w*$/u',   $key)) {
			$key = str_replace('`', '``', $key);
			return $quote  ? "`${key}`" : $key;
		}
		else	return $key;
	}

	private function _val ($val) {
		if   ($this->_is_self($val)) {
			foreach ($val() as $bind)
				$this->bind[] = $bind;
			return "(${val})";
		}
		else {
			$this->bind[] = $val;
			return '?';
		}
	}

	private function _item ($item) {

		list ($key, $val) = each($item);

		if   ($this->_is_self($val)) {
			if (is_int($key)) $key = 't' . ++$key;
			$val  =	  $this->_val       ($val);
		}
		else	$val  =	  $this->_escape_key($val);

		if   (is_string($key))
			$val .=   ' AS '
				. $this->_escape_key($key);
		return  $val;
	}

	private function _data ($data) {

		$data = (array) $data;

		foreach ($data as $key => &$val)
			if (is_string($key))
				$val =	  $this->_escape_key($key)
					. ' = '
					. $this->_val       ($val);

		return join(', ', $data);
	}

	private function _opts ($opts) {

		$opts = (array) $opts;

		foreach ($opts as $key => &$val)
			if   (is_string($key)) {
				if     ($val)	$val =      $key;
				else		unset($opts[$key]);
			}

		if ($opts) return ' ' . strtoupper(join(' ', $opts));
	}

	private function _by  ($cols) {

		$cols = (array)$cols;

		foreach ($cols as $key => &$val)
			if   (is_string($key))
				$val =	   $this->_escape_key($key)
					. ($val ? ' ASC' : ' DESC');
			else
				$val =	   $this->_escape_key($val);

		return join(', ', $cols);
	}

	private function _cond ($cond) {

		$stack = array(array(
			'cond'    => (array)$cond,
			'context' => 'AND',
			'query'   =>  array(),
		));

		while ($count = count    ($stack)) {

			$last = array_pop($stack);
			$count--;

			while (list($key, $val) = each($last['cond'])) {

				$is_int_key = is_int($key);

				if (	   ($is_int_key && is_array($val))
					||  preg_match('/^-(and|or|not)$/', $key, $match)
				)
				{
					array_push($stack, $last, array(
						'cond'    => $val,
						'context' => $is_int_key ? 'OR' : strtoupper($match[1]),
						'query'   => array(),
					));
					continue 2;
				}
				if (  $is_int_key) {
					$last['query'][] = $val;
					continue;
				}

				$key        = $this->_escape_key($key);

				if (! is_array($val)) $val = array('=' => $val);

				$term       = array();

				foreach ($val as $op => $data) {

					$is_list = FALSE;

					if     (     preg_match('/^(?:(?:!)?=|<>)$/', $op)) {
						if (! isset($data))
							$op = $op == '=' ? 'IS' : 'IS NOT';
					}
					elseif (     preg_match('/^(?:not_)?in$/',    $op))
						$is_list = TRUE;
					elseif (   ! preg_match('/^(?:[<>]=?|<=>)$/', $op)
						&& ! preg_match('/^(?:not_)?like$/',  $op)
					)
						throw new \Exception("Unknow operation: ${op}");

					$op      = strtoupper(str_replace('_',  ' ',  $op));

					if     (! is_array($data)) $data = array($data);

					if     (  $is_list) {

						foreach ($data as &$item)
							$item =                   $this->_val($item);

						$term[] = sprintf(
							"${key} ${op} (%s)", join(', ', $data)
						);
					}
					else   {
						foreach ($data as &$item)
							$item = "${key} ${op} " . $this->_val($item);

						$term[] = join(' AND ', $data);
					}
				}

				                      $val = join(' AND ', $term);
				if (count($term) > 1) $val = "(${val})";

				$last['query'][] = $val;
			}

			$is_not = $last['context'] == 'NOT';

			$query  = join(
				$is_not ? ' AND ' : " $last[context] ",
				$last['query']
			);

			if   ($count)  $query =    "(${query})";
			if   ($is_not) $query = "NOT ${query}";

			if   ($count)
				$stack[$count - 1]['query'][] = $query;
			else
				$this->sql[] = $query;
		}

		return $this;
	}
}

?>
