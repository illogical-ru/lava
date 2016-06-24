<?php

namespace Stasis;

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

// PHP >= 5.3
if (version_compare(phpversion(), '5.3') < 0)
	die('PHP 5.3+ is required');


class SQLBuilder {

	private	$sql  = array(),
		$bind = array();


	static
	public function self () {
		return new self;
	}


	public function __call ($cmd, $args) {

		$data = '';

		if     (isset($args[0]))
			$data = join(', ', (array)$args[0]);
		if     (isset($args[1]) && $args[1])
			$data = "(${data})";
		elseif ($data)
			$data = " ${data}";

		$this->sql[] = strtoupper($cmd) . $data;

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
			if (is_string($key))
				$val = "${key} AS ${val}";

		$this->sql[] =	  'SELECT' . $this->_opts($opts)
				. ' '      . join(', ',   $expr);
		return $this;
	}

	public function insert ($opts = NULL) {
		$this->sql[] =	  'INSERT' . $this->_opts($opts);
		return $this;
	}

	public function update ($table, $opts = NULL) {
		$this->sql[] =	  'UPDATE' . $this->_opts($opts)
				. ' '      . $table;
		return $this;
	}

	public function delete ($opts = NULL) {
		$this->sql[] =	  'DELETE' . $this->_opts($opts);
		return $this;
	}

	public function from ($src) {

		$src = $this->_is_self($src)	?  array($src)
						: (array)$src;
		$seq = 0;

		foreach ($src as $key => &$val)
			if     (is_string      ($key))
				$val =	  "${key} AS ${val}";
			elseif ($this->_is_self($val))
				$val =	  $this->_val($val)
					. ' AS t' . ++$seq;

		$this->sql[] = 'FROM '  . join(', ',  $src);

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

	public function union (array $select, $all = FALSE) {

		$union = array();

		foreach ($select as $sql)
			if ($this->_is_self($sql))
				$union[] = $this->_val($sql);

		$this->sql[] = join(
			' UNION ' . ($all ? 'ALL ' : ''), $union
		);

		return $this;
	}

	public function set   ($data) {

		$data = (array)$data;

		foreach ($data as $key => &$val)
			if (is_string($key))
				$val = $key . ' = ' . $this->_val($val);

		$this->sql[] = 'SET ' . join(', ', $data);

		return $this;
	}


	private function _is_self   ($sql) {
		return	   is_object($sql)
			&& get_class($sql) == get_class($this);
	}

	private function _val ($val) {
		if   ($this->_is_self($val)) {
			foreach ($val() as $bind) $this->bind[] = $bind;
			return "(${val})";
		}
		else {
			$this->bind[] = $val;
			return '?';
		}
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
			if (is_string ($key))
				$val = $key . ' ' . ($val ? 'ASC' : 'DESC');

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

					$data    = $this->_is_self($data)	?  array($data)
										: (array)$data;
					if     ($is_list) {

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
