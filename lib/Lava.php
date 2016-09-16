<?php

namespace Lava;

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

// PHP >= 5.3
if (version_compare(phpversion(), '5.3') < 0)
	die('PHP 5.3+ is required');


class App {

	public	$conf, $env, $args,
		$stash,
		$safe;

	private	$routes = array();

	static
	private	$types  = array(
		'txt'   => 'text/plain',
		'html'  => 'text/html',
		'js'    => 'text/javascript',
		'json'  => 'application/json',
		'jsonp' => 'application/javascript',
		'xml'   => 'application/xml',
	);

	public function __construct ($conf = NULL) {

		$this->conf   = new Stash ($conf);
		$this->env    = new ENV;
		$this->args   = new Args;
		$this->cookie = new Cookie;
		$this->stash  = new Stash;
		$this->safe   = new Safe  ($this->conf->safe());

		if (method_exists($this, 'init')) $this->init();
	}

	public function host ($scheme = NULL) {

		$host = $this->env->host;

		if ($scheme === TRUE) {
			$secure =  $this->env->https
				&& $this->env->https != 'off';
			$scheme = 'http' . ($secure ? 's' : '');
		}
		if (isset($scheme)) $host = "${scheme}://${host}";

		return  $host;
	}

	public function home () {
		$home = func_get_args();
		array_unshift(
			$home, $this->conf->home ? $this->conf->home : getcwd()
		);
		return  join('/', $home);
	}

	public function pub  () {
		$pub  = func_get_args();
		array_unshift(
			$pub,  $this->conf->pub	? $this->conf->pub
						: preg_replace(
							'/\/[^\/]*$/', '',
							$this->env->script
						  )
		);
		return  join('/', $pub);
	}

	public function uri  ($uri = NULL, $data = NULL, $append = FALSE) {

		if     (! isset($uri))
			$uri  = $this->env->uri;
		if     (  isset($this->routes[$uri]))
			$uri  = $this->routes[$uri]->uri($data);
		elseif (  isset($data) || $append) {
			$data = $this->args->_query($data, $append);
			if ($data) $uri	.= (strpos($uri, '?') ? '&' : '?')
					.   $data;
		}

		if     (! preg_match('/^(?:[a-zA-Z]+:\/)?\//', $uri))
			$uri  = $this->pub($uri);

		return  $uri;
	}
	public function uri_ref_or () {

		if   (	     preg_match(
				'/^([a-z]+:\/\/[^\/]+)(([^?]*).*)/',
				$this->env->referer,
				$match
			     )
			&& ! strcasecmp($match[1],   $this->host(TRUE))
			&&              $match[3] != $this->env->uri
		)
			return  $match[2];
		else
			return  call_user_func_array(
				array($this, 'uri'), func_get_args()
			);
	}
	public function url  () {

		$url  = call_user_func_array(
			array($this, 'uri'), func_get_args()
		);
		if (! preg_match('/^[a-zA-Z]+:\/\//', $url))
			$url = $this->host(TRUE) . $url;

		return  $url;
	}

	public function type ($type = NULL) {

		if     (  isset($type)) {

			$type = strtolower($type);

			if (isset(self::$types[$type]))
				$type  = self::$types[$type];
			if (      $this->conf->charset)
				$type .= "; charset={$this->conf->charset}";

			return "Content-Type: ${type}";
		}
		elseif (  $this->env->is_rewrite) {
			if (preg_match('/\.(\w*)$/', $this->env->uri, $match))
				$type  = end($match);
		}
		else	$type = $this->args->type;

		if     (! isset($type))
			$type = $this->conf->type;

		return	strtolower($type);
	}

	public function render ($handler) {

		$type = $this->type();

		if     (  isset($handler[$type]))	$handler = $handler[$type];
		elseif (  isset($handler[    0])) 	$handler = $handler[    0];
		else					return;

		if     (! headers_sent()) {
			header($this->type($type));
			header('Expires: 0');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Pragma: no-cache');
		}

		$data = is_object($handler) && is_callable($handler)
			? call_user_func($handler, $this)
			:                $handler;

		if     (isset($data)) {
			if   ($type == 'json')	echo json_encode($data);
			else			echo             $data;
		}

		return  TRUE;
	}

	public function redirect () {
		$location = call_user_func_array(
			array($this, 'url'), func_get_args()
		);
		return $this->render(array(
			'json' => array('location' =>  $location),
			function() use ($location) {
				header ('Location: ' . $location, TRUE, 301);
			},
		));
	}

	public function route       ($rule = '', $cond = NULL) {
		if (strpos($rule, '/') !== 0)
			$rule = $this->pub($rule);
		return  $this->routes[] = new Route ($rule, $cond);
	}
	public function route_get   ($rule = '') {
		return  $this->route($rule, 'GET');
	}
	public function route_post  ($rule = '') {
		return  $this->route($rule, 'POST');
	}
	public function route_match ($uri  = NULL, $env = NULL) {

		if   (isset($env))
			$this->env             = new Stash ($env);
		if   (isset($uri)) {
			$this->env->uri        = $uri;
			$this->env->is_rewrite = TRUE;
		}
		else	$uri                   = $this->env->uri;

		for ($i = count($this->routes); $i--;) {
			$route = array_shift($this->routes);
			if   ($route->name())
				$this->routes[$route->name()] = $route;
			else	$this->routes[]               = $route;
		}

		$done = 0;

		foreach ($this->routes as $route) {

			$args   = $route->test($uri, $this->env->_data());
			if (is_null($args))	continue;

			foreach ($args as $key => $val)
				$this->args->$key = $val;

			$to     = $route->to();

			if   (count($to) == 1 && is_callable(current($to)))
				$to     = array_shift($to);
			else {
				$file   = array_shift($to);
				$method = count($to)	? array_pop($to)
							: $route->name();
				if (! $method)	continue;

				require_once $file;

				if   (count($to))
					$class = join('\\', $to);
				else {
					$info  = pathinfo   ($file);
					$class = $info['filename'];
				}

				$to     = array(new $class ($this), $method);
			}

			$result = call_user_func($to, $this);

			if ($result !== FALSE)	$done++;
			if ($result !== TRUE)	break;
		}

		return  $done;
	}
}

class Stash {

	protected $data = array();

	public function __construct () {

		$args = func_num_args() == 1		? func_get_arg (0)
							: func_get_args( );
		if (is_array($args))
			foreach ($args as $key => $val)
				$this->data[$key] = $val;
	}

	public function __get   ($key) {

		$data = &$this->data;

		if (isset($data[$key]))
			return is_array($data[$key])	? end  ($data[$key])
							:       $data[$key];
	}
	public function __set   ($key, $val) {
		return $this->data[$key] = $val;
	}

	public function __call  ($key, $args) {

		$data = &$this->data;

		if (count($args)) $data[$key] = $args;

		return isset($data[$key])		? (array)$data[$key]
							:  array();
	}

	public function __isset ($key) {
		return isset($this->data[$key]);
	}
	public function __unset ($key) {
		       unset($this->data[$key]);
	}

	public function _data () {
		return $this->data;
	}
}

class ENV extends Stash {

	static
	private $aliases = array(

		'server_name'     => '=localhost',
		'server_port'     => '=80',
		'server_protocol' => '=HTTP/1.0',
		'server_software' => '=PHP',

		'user'            => 'remote_user',
		'user_addr'       => 'remote_addr',
		'user_port'       => 'remote_port',
		'user_agent'      => 'http_user_agent',

		'host'            => 'http_host server_name',
		'method'          => 'request_method =GET',
		'type'            => 'content_type',
		'length'          => 'content_length =0',
		'script'          => 'script_name php_self',
		'query'           => 'query_string',
		'referer'         => 'http_referer',
	);

	public function __construct () {

		$data  = array();

		foreach ($_SERVER as $key => $val) {

			$key    = strtolower($key);
			$data[$key] = $val;

			// accept*
			if (! preg_match('/^http_(accept(?:|_\w+))$/', $key, $match))
				continue;

			$key    = $match[1];
			$accept = array ( );

			foreach (preg_split('/[\s,]+/', $val) as $val) {
				preg_match(
					'/(.*?)(?:\s*;\s*q=(\d+(?:\.\d+)?))?$/',
					$val, $match
				);
				$accept[$match[1]] = isset($match[2]) ? $match[2] : 1;
			}
			              arsort    ($accept);
			$data[$key] = array_keys($accept);
		}

		foreach (self::$aliases as $key => $val) {
			preg_match_all('/(=)?(\S+)/', "${key} ${val}", $match);
			foreach ($match[2] as $i => $val) {
				if (! $match[1][$i])
					$val = isset($data[$val]) ? $data[$val] : NULL;
				if (  isset($val)) {
					$data[$key] = $val;
					break;
				}
			}
		}

		$uri   = isset($data['document_uri'])	? $data['document_uri']
							: $data['script'];

		$data['uri']        = isset($data['request_uri'])
			? preg_replace('/\?.*/', '', urldecode($data['request_uri']))
			: $uri;

		$data['is_rewrite'] = $data['uri']    != $uri;
		$data['is_post']    = $data['method'] == 'POST';

		parent::__construct($data);
	}
}

class Args extends Stash {

	public function __construct () {

		$data = array('get' => $_GET, 'post' => $_POST, array());

		foreach ($data as $method => $args) {

			$data[$method] = array();

			foreach ($args as $key => $val) {
				$val = $this->_normalize($val);
				if (isset($val)) $data[$method][$key] = $val;
			}
			$data[$method] = new Stash ($data[$method]);
		}

		parent::__construct($data);
	}

	public function __get ($key) {
		foreach (array_reverse($this->data) as $stash)
			if (key_exists($key, $stash->_data()))
				return $stash->$key;
	}
	public function __set  ($key, $val) {
		return end($this->data)->$key = $val;
	}

	public function __call ($key, $args) {

		if (isset($this->data[$key])) return $this->data [$key];

		if (count($args))             return $this->__set($key, $args);

		foreach (array_reverse($this->data) as $stash)
			if (key_exists($key, $stash->_data()))
				return $stash->$key();

		return  array();
	}

	public function __isset ($key) {
		$val = $this->__get($key);
		return isset($val);
	}
	public function __unset ($key) {
		foreach ($this->data as $stash)
			unset($stash->$key);
	}

	public function _query ($data, $append = FALSE) {

		if (! is_array($data)) parse_str($data, $data);

		$query = $append ? $this->get()->_data() : array();

		foreach ($data as $key => $val)
			$query[$key] = $this->_normalize($val, FALSE);

		return  http_build_query($query);
	}

	private function _normalize ($val, $gpc = NULL) {

		if   (! isset   ($gpc)) $gpc = get_magic_quotes_gpc();

		if   (  is_array($val)) {
			foreach ($val as $index => &$item) {
				$item = $this->_normalize($item, $gpc);
				if (! isset($item)) unset($val[$index]);
			}
			if (count($val)) return $val;
		}
		else {
			$val = trim($val);
			if ($val != '')
				return $gpc ? stripslashes($val) : $val;
		}
	}
}

class Cookie extends Stash {

	public function __construct () {
		parent::__construct($_COOKIE);
	}

	public function __set  ($key, $val) {

		$data = $this->_normalize($key, $val);
		$opts = array_slice(func_get_args(), 2);

		// expire
		if (isset($opts[0])) $opts[0] = Date::time_offset($opts[0]);

		$done = 0;

		foreach ($data as $item)
			$done += call_user_func_array(
				'setcookie', array_merge($item, $opts)
			);

		return  $done;
	}

	public function __call ($key, $args) {
		if     (count($args))
			return call_user_func_array(
				array      ($this, '__set'),
				array_merge(array($key), $args)
			);
		elseif (isset($this->data[$key]))
			return (array)$this->data[$key];
		else
			return  array();
	}

	private function _normalize ($key, $val) {
		if   (is_array($val)) {
			$data = array();
			foreach ($val as $index => $item)
				$data = array_merge($data, $this->_normalize(
					"${key}[${index}]", $item
				));
			return  $data;
		}
		else	return  array(array($key, $val));
	}
}

class Safe {

	private $id   =  0,
		$sign = '',
		$algo = 'md5',
		$salt = '0123456789abcdef',
		$salt_len;

	public function __construct ($opts = NULL) {

		foreach (array('sign', 'algo', 'salt') as $key)
			if (isset($opts[$key])) $this->$key = $opts[$key];

		$this->salt_len = strlen($this->salt) - 1;
	}

	public function uuid () {
		return $this->_hash(uniqid(), getmypid(), $this->id++);
	}
	public function uuid_signed () {
		$uuid = $this->uuid();
		return array(
			$uuid . $this->_hash($uuid, $this->sign), $uuid
		);
	}

	public function check ($signed) {
		$half = strlen($signed) >> 1;
		list($uuid, $sign) = sscanf($signed, "%${half}s %${half}s");
		if ($sign == $this->_hash($uuid, $this->sign)) return $uuid;
	}

	public function salt ($size) {
		$salt = '';
		while  ($size-- > 0)
			$salt .= $this->salt{mt_rand(0, $this->salt_len)};
		return  $salt;
	}

	private function _hash () {
		return hash($this->algo, join(':', func_get_args()));
	}
}

class Route {

	private	$cond        = array(),
		$segs, $regexp,
		$name, $to;

	static
	private	$placeholder = array(
		':' => '([^\/]+)',
		'#' => '([^\/]+?)(?:\.\w*)?',
		'*' => '(.+)',
	);

	public function __construct ($rule, $cond = NULL) {

		$placeholder = self::$placeholder;
		$prefix      = preg_quote(join('', array_keys($placeholder)));

		$segs        = preg_split(
			"/([${prefix}])([\w-]+)/", $rule,
			-1, PREG_SPLIT_DELIM_CAPTURE
		);
		$regexp      = array();

		foreach ($segs as $i => $seg)
			if     (!  ($i      % 3))
				$regexp[] = preg_quote  ($seg, '/');
			elseif (! (($i - 1) % 3)) {
				$regexp[] = $placeholder[$seg];
				unset($segs[$i]);
			}

		if (! preg_match('/^\/*$/', end($segs)))
				$regexp[] = '(?:\.\w*)?';

		$this->segs   = array_values($segs);
		$this->regexp = sprintf     ('/^%s$/', join('', $regexp));

		if     (is_string($cond))	$this->cond['method'] = $cond;
		elseif (is_array ($cond))	$this->cond           = $cond;
	}

	public function cond ($cond) {
		foreach ($cond  as  $key => $val)
			$this->cond[$key] = $val;
		return  $this;
	}

	public function name () {
		if   (func_num_args()) {
			$this->name = func_get_arg(0);
			return $this;
		}
		else	return $this->name;
	}

	public function to   () {
		if   (func_num_args()) {
			$this->to   = func_get_args();
			return $this;
		}
		else	return $this->to;
	}

	public function test ($uri, $env) {

		foreach ($this->cond as $key => $cond)
			if     (! isset     ($env[$key])) {
				if   (  isset     ($cond))		return;
				else					continue;
			}
			elseif (  is_array  ($cond)) {
				if   (! in_array  ($env[$key], $cond))	return;
			}
			elseif (  preg_match('/^\/.+\/[imsuxADEJSUX]*$/', $cond)) {
				if   (! preg_match($cond, $env[$key]))	return;
			}
			elseif (  $cond !== $env[$key])			return;

		if (! preg_match($this->regexp, $uri, $matches))	return;

		$args = array();

		foreach ($this->segs as $i => $seg)
			if ($i & 1) $args[$seg] = $matches[++$i / 2];

		return  $args;
	}

	public function uri ($args = array()) {
		$uri = array();
		foreach ($this->segs as $i => $seg)
			$uri[] = $i & 1 && isset($args[$seg])	? $args[$seg]
								:       $seg;
		return  join('', $uri);
	}
}

class Date {

	static
	private $offset = array(
		's' => 1,
		'm' => 60,
		'h' => 3600,
		'D' => 86400,
		'W' => 604800,		//   7D
		'M' => 2592000,		//  30D
		'Y' => 31536000,	// 365D
	);

	static
	public function time_offset ($offset) {

		if (	   preg_match('/^([-+]?\d+)(\D)$/', $offset, $match)
			&& isset     (self::$offset[$match[2]])
		)
			$offset = $match[1] * self::$offset[$match[2]];

		return time() + $offset;
	}
}

?>
