<?php

namespace Wave;

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

// PHP >= 5.3
if (version_compare(phpversion(), '5.3') < 0)
	die('PHP 5.3+ is required');

// PDO extension
if (! get_extension_funcs('PDO'))
	die('PDO is required');


class PDO extends \PDO {

	private $error = NULL;


	public function error () {
		return $this->error;
	}


	public function begin () {
		return $this->beginTransaction();
	}
	public function rollback () {
		return $this->rollBack();
	}
	public function in_transaction () {
		return $this->inTransaction();
	}

	public function execute ($query, array $bind = NULL) {

		$this->error = NULL;

		$sth = $this->prepare($query);

		if   ($sth->execute($bind))
			return  $sth ->rowCount();
		else
			list(,, $this->error) = $sth->errorInfo();
	}

	public function fetch_assoc ($query, array $bind = NULL) {

		$this->error = NULL;

		$sth = $this->prepare($query);

		$sth->setFetchMode(PDO::FETCH_ASSOC);

		if   ($sth->execute($bind))
			return  $sth ->fetchAll();
		else
			list(,, $this->error) = $sth->errorInfo();
	}
}

class Schema {

	private $pdo,
		$model;


	public function __construct  ($opts,  array $attr = NULL) {

		if (is_string($opts)) $opts = array('dsn' => $opts);

		foreach (array('dsn', 'username', 'password') as $key)
			if (! isset($opts[$key])) $opts[$key] = NULL;

		if (isset($opts['persistent']))
			$attr[PDO::ATTR_PERSISTENT] = $opts['persistent'];
		if (isset($opts['timeout']))
			$attr[PDO::ATTR_TIMEOUT]    = $opts['timeout'];

		$this->pdo = new PDO (
			$opts['dsn'], $opts['username'], $opts['password'],
			$attr
		);

		if (isset($opts['charset']))
			$this->pdo->exec("SET NAMES $opts[charset]");
	}

	public function __destruct () {
		if ($this->pdo) $this->pdo = NULL;
	}


	public function pdo () {
		return $this->pdo;
	}

	public function model ($class, array $opts = NULL) {

		if (  $opts) ksort($opts);

		$hash = hash('md5', serialize(array($class => $opts)));

		if (! isset($this->model[$hash])) {

			if (! class_exists($class))
				throw new \Exception("Model not found: ${class}");

			$this->model[$hash] = new $class ($this->pdo, $opts);
		}

		return	$this->model[$hash];
	}
}

class SQLBuilder {

	private	$sql  = array(),
		$bind = array();


	public function __call ($cmd, $args) {

		$data = '';

		if     (isset($args[0]))
			$data = join(', ', (array)$args[0]);
		if     (isset($args[1]) && $args[1])
			$data = "(${data})";
		elseif ($data)
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

class Model {

	protected $pdo, $opts,
		  $error;


	public function __construct (PDO $pdo, $opts = NULL) {
		$this->pdo  = $pdo;
		$this->opts = $opts;
	}


	public function __toString () {
		return get_class($this);
	}


	public function error () {
		return $this->error;
	}

	public function alias () {
		return $this->_camel2snake(preg_replace(
			'/(?:.*\\\\|Model(?=.))/', '', $this
		));
	}
	public function table () {

		$opts  = $this->opts;

		$table = isset   ($this->table)
				? $this->table
				: $this->alias();

		if (isset($opts['prefix']))
			$table = $opts['prefix'] . $table;

		return  $table;
	}

	public function columns () {
		return isset    ($this->columns)
			?        $this->columns
			:  array();
	}

	public function unique () {
		return isset    ($this->unique)
			? (array)$this->unique
			:  array();
	}

	public function column_list ($name) {

		$columns = $this->columns();

		return isset($columns[$name]['list'])
			?    $columns[$name]['list']
			:    array();
	}
	public function column_default ($name) {

		$columns = $this->columns();
		$method  = "${name}_default";

		if     (isset ($columns[$name]['default']))
			return $columns[$name]['default'];
		elseif (method_exists($this, $method))
			return $this->$method();
	}

	public function select ($cond = NULL, $opts = NULL) {

		$columns  = isset       ($opts['columns'])
				? (array)$opts['columns']
				:  array('*');

		if (isset($opts['+columns']))
			foreach ( (array)$opts['+columns'] as $item)
				$columns[] = $item;

		$sel_opts = array();

		foreach (array('distinct', 'high_priority') as $key)
			$sel_opts[$key] = isset($opts[$key]) && $opts[$key];

		$sql      = $this->_sql();

		$sql	->select($columns, $sel_opts)
			->from  (array($this->alias() => $this->table()));

		$imports  = array();

		if (method_exists($this, 'import'))
			$imports[(string)$this] = $this;

		foreach (array('left_join', 'join', 'right_join') as $join) {

			if (!     isset($opts[$join])) continue;

			foreach ((array)$opts[$join] as $key => $val) {

				if   (  is_int($key)) {
					$name     =        $val;
					$rel_cond =  array();
				}
				else {
					$name     =        $key;
					$rel_cond = (array)$val;
				}

				if   (! isset($this->rels[$name]))
					throw new \Exception(
						"Relation not found: ${this}.${name}"
					);

				$rels  = $this->rels [$name];
				$alias = $this->alias();

				foreach ($rels as $model => $links) {

					$model = new $model ($this->pdo, $this->opts);

					if (method_exists($model, 'import'))
						$imports[(string)$model] = $model;

					foreach ($links as $k1 => $k2) {

						if (preg_match('/^\w+$/u', $k1))
							$k1 = $alias .          ".${k1}";
						if (preg_match('/^\w+$/u', $k2))
							$k2 = $model->alias() . ".${k2}";

						$rel_cond[] = "${k1} = ${k2}";
					}

					$alias = $model->alias();

					$sql->$join(
						array       ($alias => $model->table()),
						array_splice($rel_cond, 0)
					);
				}
			}
		}

		if (  $cond) $sql->where($cond);

		foreach (array('group_by', 'having', 'order_by', 'limit', 'offset') as $key)
			if (isset($opts[$key])) $sql->$key($opts[$key]);

		$sth      = $this->pdo->prepare((string)$sql);

		$this->error = NULL;

		if (! $sth->execute($sql())) {
			list(,, $this->error) = $sth->errorInfo();
			return;
		}

		$key      = isset                  ($opts['key'])
				? array_flip((array)$opts['key'])
				: array();

		$data     = array();

		while ($item = $sth->fetch(PDO::FETCH_ASSOC)) {

			foreach ($imports as $model)
				$model->import($item);

			if   ($key) {

				foreach (array_keys($key) as $name)
					$key[$name] = isset($item[$name])
							?   $item[$name]
							:   NULL;

				$data[join(':', $key)] = $item;
			}
			else	$data[               ] = $item;
		}

		return $data;
	}

	public function insert (array $data) {

		$this->_set_defaults ($data);

		$error = $this->_test(array($data));

		list($this->error) = $error;
		if ($error) return;

		if (method_exists($this, 'export'))
			$this->export($data);

		$sql   = $this->_sql();

		$sql	->insert()
			->into  ($this->table())
			->values($data);

		return $this->_execute($sql);
	}
	public function insert_id ($data, $name = NULL) {
		if ($this->insert ($data))
			return $this->pdo->lastInsertId($name);
	}

	public function populate (array $data) {

		foreach ($data as &$item)
			$this->_set_defaults($item);

		$this->error = $this->_test ($data);
		if ($this->error) return;

		if (method_exists($this, 'export'))
			foreach ($data as &$item)
				$this->export($item);

		$sql = $this->_sql();

		$sql	->insert()
			->into  ($this->table());

		call_user_func_array(array($sql, 'values'), $data);

		return $this->_execute($sql);
	}

	public function update ($cond, $data, $opts = NULL) {

		$error = $this->_test(array($data), $cond);

		list($this->error) = $error;
		if ($error) return;

		if (method_exists($this, 'export'))
			$this->export($data);

		$sql   = $this->_sql();

		$sql	->update($this->table())
			->set   ($data);

		if ($cond) $sql->where($cond);

		foreach (array('order_by', 'limit') as $key)
			if (isset($opts[$key])) $sql->$key($opts[$key]);

		return $this->_execute($sql);
	}

	public function delete ($cond = NULL, $opts = NULL) {

		$sql = $this->_sql();

		$sql	->delete()
			->from  ($this->table());

		if ($cond) $sql->where($cond);

		foreach (array('order_by', 'limit') as $key)
			if (isset($opts[$key])) $sql->$key($opts[$key]);

		return $this->_execute($sql);
	}

	public function single ($cond = NULL, $opts = NULL) {

		$opts = (array) $opts;
		$opts['limit'] = 1;

		$data = $this->select($cond,  $opts);
		if ($data) return array_shift($data);
	}

	public function count ($cond = NULL) {
		$data = $this->single($cond, array(
			'columns' => 'COUNT(*) AS count',
		));
		if ($data) return (int)$data['count'];
	}

	public function truncate () {

		$sql = $this->_sql();

		$sql	->truncate()
			->table   ($this->table());

		return $this->_execute($sql);
	}

	protected function _sql () {
		return new SQLBuilder;
	}

	protected function _execute (SQLBuilder $sql) {

		$result = $this->pdo->execute((string)$sql, $sql());

		if   (is_null ($result))
			$this->error = $this->pdo->error();
		else
			return $result;
	}

	protected function _set_defaults (&$data) {

		if (! is_array($data)) return;

		foreach ($this->columns() as $key => $meta)
			if (	   !       key_exists($key,      $data)
				&& ! (	   key_exists('default', $meta)
					&& is_null   ($meta['default'])
				     )
			)
				$data[$key] = $this->column_default($key);
	}

	protected function _test (array $data, $not = NULL) {

		$error   = NULL;

		$columns = $this->columns();
		if (! $columns)	return;

		foreach ($data as $i => $item)
			foreach ($item as $key => $val) {

				if     (!  isset($columns[$key])) {
					if (is_string($key))
						$error[$i][$key] = 'unknown';
					continue;
				}

				$meta = $columns[$key];

				if     (   isset($val)) {
					if (isset($meta['test'])) {

						$test = new Test ($meta['test']);

						if (! $test->ok($val))
							$error[$i][$key] = 'invalid';
					}
					if (isset($meta['list'])) {
						if (! in_array($val, $meta['list']))
							$error[$i][$key] = 'invalid';
					}
				}
				elseif (! (isset($meta['null']) && $meta['null']))
							$error[$i][$key] = 'null';
			}

		$unique  = $this->unique();

		$index   = array();

		foreach ($unique as $keys)
			foreach ($data as $i => $item) {

				$stack =  array();

				foreach ((array)$keys as $key) {

					if (	     isset($error[$i][$key])
						|| ! isset($item     [$key])
					)
						continue 2;

					$stack[$key]         = $item[$key];
				}
				foreach ($stack as $key => $val)
					$index[$key]['in'][] = $val;
			}

		if (! $index)	return $error;

		$cond    = array('-or' => $index);

		if (  $not)	$cond['-not'] = $not;

		$result  = $this->select(
			$cond, array('columns' => array_keys($index))
		);
		if (! $result)	return $error;

		foreach ($unique as $keys) {

			$keys  = (array)$keys;
			$index =  array();

			foreach ($result as       $item) {

				$stack = array();

				foreach ($keys as $key)
					if   (key_exists($key, $item))
						$stack[$key] = $item[$key];
					else
						continue 2;

				$index[serialize($stack)] = TRUE;
			}
			foreach ($data   as $i => $item) {

				$stack = array();

				foreach ($keys as $key)
					if   (key_exists($key, $item))
						$stack[$key] = $item[$key];
					else
						continue 2;

				if (isset($index[serialize($stack)]))
					$error[$i][join(':', $keys)] = 'exists';
			}
		}

		return $error;
	}

	protected function _camel2snake ($val) {
		return strtolower(preg_replace(
			array('/(\B)([A-Z]+)/', '/([A-Z]+)([A-Z][^A-Z])/'),
			"\${1}_\${2}",
			$val
		));
	}
}

class Test {

	private $stack = array();


	public function __construct ($tests = NULL) {
		$this->add($tests);
	}


	public function __call ($key,  $args) {
		array_unshift  ($args, $key);
		$this->add(join(':',   $args));
		return $this;
	}


	public function add ($tests) {

		foreach ((array)$tests  as  $test)
			if     (is_object  ($test) && is_callable($test))
				$this->stack[] = $test;
			elseif (is_object  ($test))
				throw new \Exception('Object in Model\Test');
			elseif (preg_match ('/^\/.*\/[imsuxADEJSUX]*$/', $test))
				$this->stack[] = function($val) use ($test) {
					return preg_match($test,     $val);
				};
			else   {
				$opts =         explode(':', $test);
				$name = 'is_' . array_shift ($opts);

				if (! method_exists($this, $name))
					throw new \Exception("Bad test: ${name}");

				$self = array($this, $name);

				$this->stack[] = function($val) use ($self, $opts) {
					array_unshift($opts, $val);
					return call_user_func_array ($self, $opts);
				};
			}

		return $this;
	}

	public function ok () {

		$args = func_get_args();

		foreach ($this->stack as $test)
			if (! call_user_func_array($test, $args)) return FALSE;

		return  TRUE;
	}


	public static function is_int ($val, $size = 4, $unsigned = FALSE) {
		$size =    pow(256, $size);
		return	   is_numeric ($val)
			&& $val >= ($unsigned ? 0     : -$size / 2)
			&& $val <= ($unsigned ? $size :  $size / 2) - 1;
	}
	public static function is_tinyint  ($val, $unsigned = FALSE) {
		return self::is_int ($val, 1, $unsigned);
	}
	public static function is_smallint ($val, $unsigned = FALSE) {
		return self::is_int ($val, 2, $unsigned);
	}
	public static function is_mediumint($val, $unsigned = FALSE) {
		return self::is_int ($val, 3, $unsigned);
	}
	public static function is_integer  ($val, $unsigned = FALSE) {
		return self::is_int ($val, 4, $unsigned);
	}
	public static function is_bigint   ($val, $unsigned = FALSE) {
		return self::is_int ($val, 8, $unsigned);
	}

	public static function is_numeric  ($val, $prec = 0, $scale = 0) {
		if (!  is_numeric($val)) return;
		if (! ($prec || $scale)) return TRUE;
		return	   $prec  && $prec <= 1000
			&& $scale <= $prec
			&& pow(10, $prec - $scale) > abs($val);
	}

	public static function is_boolean ($val) {
		return is_bool($val);
	}

	public static function is_string ($val, $min, $max = NULL) {

		if (! (is_numeric($val) || is_string($val))) return;

		$len = is_callable('mb_strlen')	? mb_strlen($val)
						:    strlen($val);

		return $len >= $min && (! $max || $len <= $max);
	}
	public static function is_char   ($val, $size = 1) {
		return self::is_string($val, $size, $size);
	}

	public static function is_email ($val) {
		return filter_var($val, FILTER_VALIDATE_EMAIL);
	}

	public static function is_url ($val) {
		return filter_var($val, FILTER_VALIDATE_URL);
	}

	public static function is_ipv4 ($val) {
		return filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

	public static function is_date ($val) {
		return	   is_string ($val)
			&& preg_match('/^(\d+)-(\d+)-(\d+)$/', $val, $match)
			&& checkdate ($match[2], $match[3], $match[1]);
	}
	public static function is_time ($val) {
		return	   is_string ($val)
			&& preg_match(
				'/^(?:[01]?\d|2[0-3]):[0-5]?\d(?::[0-5]?\d)?$/',
				$val
			   );
	}
	public static function is_datetime ($val) {
		return	   is_string ($val)
			&& preg_match('/^(\S+)\s(\S+)$/',      $val, $match)
			&& self::is_date($match[1])
			&& self::is_time($match[2]);
	}

	public static function is_less_than    ($val, $num = 0) {
		return is_numeric($val) && $val < $num;
	}
	public static function is_greater_than ($val, $num = 0) {
		return is_numeric($val) && $val > $num;
	}
}

?>
