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


class Schema {

	private $pdo, $error,
		$model;


	public function __construct ($opts, array $attr = array()) {

		if (is_string($opts)) $opts = array('dsn' => $opts);

		foreach (array('dsn', 'username', 'password') as $key)
			if (! isset($opts[$key])) $opts[$key] = NULL;

		if (isset($opts['persistent']))
			$attr[\PDO::ATTR_PERSISTENT] = $opts['persistent'];
		if (isset($opts['timeout']))
			$attr[\PDO::ATTR_TIMEOUT]    = $opts['timeout'];

		try   {
			$this->pdo = @new \PDO (
				$opts['dsn'],
				$opts['username'], $opts['password'],
				$attr
			);
		}
		catch (\PDOException $e) {
			$this->error = $e->getMessage();
			return;
		}

		if (isset($opts['charset']))
			$this->pdo->exec("SET NAMES $opts[charset]");
	}

	public function error () {
		return $this->error;
	}

	public function begin () {
		return $this->pdo->beginTransaction();
	}
	public function commit () {
		return $this->pdo->commit();
	}
	public function rollback () {
		return $this->pdo->rollBack();
	}
	public function in_transaction () {
		return $this->pdo->inTransaction();
	}

	public function execute ($query, $bind = NULL) {

		$this->error = NULL;

		$sth = $this->pdo->prepare($query);

		if   ($sth->execute($bind))
			return  $sth ->rowCount();
		else
			list(,, $this->error) = $sth->errorInfo();
	}

	public function fetch_assoc ($query, $bind = NULL) {

		$this->error = NULL;

		$sth = $this->pdo->prepare($query);

		$sth->setFetchMode(\PDO::FETCH_ASSOC);

		if   ($sth->execute($bind))
			return  $sth ->fetchAll();
		else
			list(,, $this->error) = $sth->errorInfo();
	}

	public function model ($class, $opts = NULL) {

		$hash = hash('md5', serialize(func_get_args()));

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
			$all ? ' UNION ALL ' : ' UNION ', $union
		);

		return $this;
	}

	public function set ($data) {
		$this->sql[] = 'SET ' . $this->_data($data);
		return $this;
	}

	public function values    ($data) {

		$data    = (array) $data;
		$args    = func_get_args();

		$has_key = NULL;

		foreach ($data as $key => &$val) {
			if (is_string($key)) $has_key = TRUE;
			$val = $this->_val($val);
		}

		$sql     = sprintf('VALUES (%s)', join(', ', $data));

		if ($has_key)
			$sql = sprintf(
				"(%s) ${sql}", join(', ', array_keys($data))
			);

		array_shift($args);

		foreach ($args as $arg) {

			$arg  = (array)$arg;
			$item =  array();

			foreach (array_keys($data) as $key)
				$item[] = $this->_val(
					isset($arg[$key]) ? $arg[$key] : NULL
				);

			$sql .= sprintf(', (%s)', join(', ', $item));
		}

		$this->sql[] = $sql;

		return $this;
	}

	public function on_duplicate_key_update ($data) {
		$this->sql[] =	  'ON DUPLICATE KEY UPDATE '
				. $this->_data  ($data);
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

	private function _data ($data) {

		$data = (array) $data;

		foreach ($data as $key => &$val)
			if (is_string($key))
				$val = $key . ' = ' . $this->_val($val);

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
			if (is_string ($key))
				$val = $key . ($val ? ' ASC' : ' DESC');

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

class Model {

	protected $pdo, $opts,
		  $error;


	public function __construct (\PDO $pdo, $opts = NULL) {
		$this->pdo  = $pdo;
		$this->opts = $opts;
	}


	public function error () {
		return $this->error;
	}

	public function alias () {
		return $this->_camel2snake(preg_replace(
			'/.*\\\\/', '', get_class($this)
		));
	}
	public function table () {

		$opts  = $this->opts;

		$table = isset   ($this->table)
				? $this->table
				: $this->alias();

		if (isset($opts['prefix']))
			$table  = "$opts[prefix]${table}";
		if (isset($opts['postfix']))
			$table .= "$opts[postfix]";

		return  $table;
	}

	public function columns () {
		return isset($this->columns)	? $this->columns
						: array();
	}

	public function test ($data) {

		$this->error = NULL;

		if (! is_array($data))	return TRUE;

		$columns = $this->columns();
		if (! $columns)		return TRUE;

		foreach ($data as $key => $val) {

			if     (!  isset($columns[$key])) {

				if (is_string($key))
					$this->error[$key] = 'unknown';

				continue;
			}

			$meta = $columns[$key];

			if     (   isset($val)) {
				if (isset($meta['test'])) {

					$test = new Test ($meta['test']);

					if (! $test->ok($val))
						$this->error[$key] = 'invalid';
				}
			}
			elseif (! (isset($meta['null']) && $meta['null']))
				$this->error[$key] = 'null';
		}

		return ! $this->error;
	}

	public function select ($cond = NULL, $opts = NULL) {

		$columns = isset($opts['columns'])
			? (array)$opts['columns']
			:  array('*');

		if (isset($opts['+columns']))
			foreach ((array)$opts['+columns'] as $column)
				$columns[] = $column;

		$sql     = $this->_sql_builder();

		$sql	->select($columns)
			->from  (array($this->table() => $this->alias()));

		if ($cond) $sql->where($cond);

		foreach (array('group_by', 'having', 'order_by', 'limit', 'offset') as $key)
			if (isset($opts[$key]))
				$sql->$key($opts[$key]);

		$sth     = $this->pdo->prepare((string)$sql);

		$sth->setFetchMode(\PDO::FETCH_ASSOC);

		$this->error = NULL;

		if (! $sth->execute($sql())) {
			list(,, $this->error) = $sth->errorInfo();
			return;
		}

		$data    = $sth->fetchAll();

		if (method_exists($this, 'import'))
			foreach ($data as &$item) $this->import($item);

		return $data;
	}

	public function insert ($data) {

		$data = $this->_ext_data($data);

		if (! $this->test($data)) return;

		if (  method_exists($this, 'export'))
			$this->export($data);

		$sql  = $this->_sql_builder();

		$sql	->insert()
			->into  ($this->table())
			->values($data);

		return $this->_execute($sql);
	}

	public function insert_id ($data, $name = NULL) {
		if ($this->insert ($data))
			return $this->pdo->lastInsertId($name);
	}

	public function insert_or_update ($data, $update) {

		$error = NULL;
		$data  = $this->_ext_data($data);

		if (! $this->test($data))
			$error['insert'] = $this->error;
		if (! $this->test($update))
			$error['update'] = $this->error;

		$this->error = $error;
		if (  $error)  return;

		if (  method_exists($this, 'export')) {
			$this->export($data);
			$this->export($update);
		}

		$sql  = $this->_sql_builder();

		$sql	->insert()
			->into  ($this->table())
			->values($data)
			->on_duplicate_key_update($update);

		return $this->_execute($sql);
	}

	public function populate (array $data) {

		$error      = NULL;
		$has_export = method_exists($this, 'export');

		foreach ($data as $i => &$item) {

			$item = $this->_ext_data($item);

			if     (! $this->test($item))
				$error[$i] = $this->error;
			elseif (  $has_export)
				$this->export($item);
		}

		$this->error = $error;
		if (  $error)  return;

		$sql        = $this->_sql_builder();

		$sql	->insert()
			->into  ($this->table());

		call_user_func_array(
			array($sql, 'values'), $data
		);

		return $this->_execute($sql);
	}

	public function update ($data, $cond = NULL, $opts = NULL) {

		if (! $this->test($data)) return;

		if (  method_exists($this, 'export'))
			$this->export($data);

		$sql = $this->_sql_builder();

		$sql	->update($this->table())
			->set   ($data);

		if ($cond) $sql->where($cond);

		foreach (array('order_by', 'limit') as $key)
			if (isset($opts[$key]))
				$sql->$key($opts[$key]);

		return $this->_execute($sql);
	}

	public function delete ($cond = NULL, $opts = NULL) {

		$sql = $this->_sql_builder();

		$sql	->delete()
			->from  ($this->table());

		if ($cond) $sql->where($cond);

		foreach (array('order_by', 'limit') as $key)
			if (isset($opts[$key]))
				$sql->$key($opts[$key]);

		return $this->_execute($sql);
	}

	public function single ($cond = NULL, $opts = NULL) {

		$opts = (array) $opts;
		$opts['limit'] = 1;

		$data = $this->select($cond, $opts);
		if ($data) return array_shift($data);
	}

	public function count ($cond = NULL) {
		$data = $this->single($cond, array(
			'columns' => 'COUNT(*) AS count',
		));
		if ($data) return (int)$data['count'];
	}

	public function truncate () {

		$sql = $this->_sql_builder();

		$sql	->truncate()
			->table   ($this->table());

		return $this->_execute($sql);
	}


	protected function _sql_builder () {
		return new SQLBuilder;
	}

	protected function _execute (SQLBuilder $sql) {

		$this->error = NULL;

		$sth = $this->pdo->prepare((string)$sql);

		if   ($sth->execute($sql()))
			return  $sth ->rowCount();
		else
			list(,, $this->error) = $sth->errorInfo();
	}

	protected function _ext_data ($data) {

		if (! is_array($data)) return $data;

		$columns = $this->columns();

		foreach ($columns as $key => $meta)
			if     (key_exists($key,      $data))
				continue;
			elseif (key_exists('default', $meta)) {
				if (isset($meta['default']))
					$data[$key] = $meta['default'];
			}
			else	$data[$key] = NULL;

		return $data;
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

		$len = is_callable ('mb_strlen')
			? mb_strlen($val)
			:    strlen($val);

		if (!  isset($max))    $max  = $min;
		return $len >= $min && $len <= $max;
	}
	public static function is_char   ($val, $size = 1) {
		return self::is_string($val, $size);
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
