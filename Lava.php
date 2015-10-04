<?php

namespace Lava;

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright 2015 illogical
 * Released under the MIT license
 */

// PHP >= 5.3
if (version_compare(phpversion(), '5.3') < 0)
	die('PHP 5.3+ is required');


class App {

	public  $conf, $env, $args,
		$stash,
		$safe;

	public function __construct ($conf = NULL) {

		$this->conf  = new Conf ($conf);
		$this->env   = new ENV;
		$this->args  = new Args;
		$this->stash = new Stash;
		$this->safe  = new Safe ($this->conf->safe());

		if (method_exists($this, 'init')) $this->init();
	}

	public function name () {
		$args = func_get_args();
		if ($this->conf->name) array_unshift($args, $this->conf->name);
		return  join('_', $args);
	}

	public function host ($scheme = NULL) {

		$host = $this->env->host;

		if ($scheme === TRUE) {
			$secure =  $this->env->https
				&& $this->env->https != 'off';
			$scheme = 'http' . ($secure ? 's' : '');
		}
		if (isset  ($scheme)) $host = "${scheme}://${host}";

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

	public function uri  ($uri = NULL, $query = NULL, $append = NULL) {

		if (! isset($uri)) $uri = $this->env->uri;

		if (! preg_match('/^(?:[a-zA-Z]+:\/)?\//', $uri))
			$uri   = $this->pub($uri);
		if (  is_array($query) || $append)
			$query = $this->args->_query($query, $append);
		if (  $query)
			$uri  .= (strpos($uri, '?') ? '&' : '?') . $query;

		return  $uri;
	}
	public function url () {
		$url  = call_user_func_array(
			array($this, 'uri'), func_get_args()
		);
		if (! preg_match('/^[a-zA-Z]+:\/\//', $url))
			$url = $this->host(TRUE) . $url;
		return  $url;
	}

	public function render ($handler) {

		$type = $this->env->is_rewrite
			? preg_replace(
				'/.*?(?:\.(\w+))?$/', '${1}', $this->env->uri
			  )
			: $this->args->type;

		$type = $type ? strtolower($type) : 'html';

		if     (! isset($handler[$type])) return;

		if     ($type == 'html')	$mime = 'text/html';
		elseif ($type == 'js')		$mime = 'text/javascript';
		elseif ($type == 'json')	$mime = 'application/json';
		elseif ($type == 'jsonp')	$mime = 'application/javascript';
		else				$mime = 'text/plain';

		if     ($this->conf->charset)
			$mime .= '; charset=' . $this->conf->charset;

		header("Content-Type: ${mime}");
		header('Expires: 0');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Pragma: no-cache');

		if     (is_callable($handler[$type]))
			$content = call_user_func($handler[$type], $this);
		else
			$content =                $handler[$type];

		if     (isset($content)) {
			if   ($type == 'json')	echo json_encode($content);
			else			echo             $content;
		}

		exit;
	}

	public function redirect () {
		$url = call_user_func_array(
			array($this, 'url'), func_get_args()
		);
		header("Location: ${url}", TRUE, 301);
	}
}

class Stash {

	protected $data = array();

	public function __construct () {

		$args = func_num_args() == 1		? func_get_arg (0)
							: func_get_args( );
		if (  is_array($args))
			foreach ($args as $key => $val)
				$this->data[$key] = $val;
	}

	public function __get  ($key) {

		$data = $this->data;

		if (  isset($data[$key]))
			return is_array($data[$key])	? end  ($data[$key])
							:       $data[$key];
	}
	public function __set  ($key, $val) {
		return $this->data[$key] = $val;
	}

	public function __call ($key, $vals) {

		$data = &$this->data;

		if (  count($vals))       $data[$key] = $vals;

		if (! isset($data[$key])) return array();

		return is_array($data[$key])		?       $data[$key]
							: array($data[$key]);
	}

	public function _data () {
		return $this->data;
	}
}

class Conf extends Stash {}

class ENV  extends Stash {

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
					'/(.+?)(?:\s*;\s*q=(\d+(?:\.\d+)?))?$/',
					$val, $match
				);
				$accept[$match[1]] = isset($match[2]) ? $match[2] : 1;
			}
			              arsort    ($accept);
			$data[$key] = array_keys($accept);
		}

		$alias = array(

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

		foreach ($alias as $key => $val) {
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

	public function _normalize ($val, $gpc = NULL) {

		if   (! isset   ($gpc)) $gpc = get_magic_quotes_gpc();

		if   (  is_array($val)) {
			foreach ($val as $key => &$item) {
				$item = $this->_normalize($item, $gpc);
				if (! isset($item)) unset($val[$key]);
			}
			if (count($val)) return $val;
		}
		else {
			$val = trim($val);
			if ($val != '')
				return $gpc ? stripslashes($val) : $val;
		}
	}

	public function _query ($data, $append = FALSE) {

		$query = $append ? $this->get()->_data() : array();

		if (is_string($data)) parse_str($data, $data);

		foreach ($data as $key => $val)
			$query[$key] = $this->_normalize($val, FALSE);

		return  http_build_query($query);
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

?>
