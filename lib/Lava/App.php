<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;

use Lava\Stash;
use Lava\Env;
use Lava\Args;
use Lava\Cookie;
use Lava\Session;
use Lava\Safe;
use Lava\Route;
use Lava\Validator;
use Lava\SQLBuilder;


class App {

	public	$conf,
		$stash,
		$env, $args, $cookie, $session,
		$safe;

	private	$routes = array();

	static
	private	$types  = array(
		           'application/octet-stream',
		'txt'   => 'text/plain',
		'html'  => 'text/html',
		'js'    => 'text/javascript',
		'json'  => 'application/json',
		'jsonp' => 'application/javascript',
	);


	public function __construct ($conf = NULL) {

		$this->conf    = new Stash ($conf);
		$this->stash   = new Stash;
		$this->env     = new Env;
		$this->args    = new Args;
		$this->cookie  = new Cookie;
		$this->session = new Session;
		$this->safe    = new Safe  ($this->conf->safe());

		if (method_exists($this, 'init')) $this->init();
	}


	public function host ($scheme = NULL, $subdomain = NULL) {

		$host = $this->conf->host	? $this->conf->host
						: $this->env ->host;
		if ($scheme === TRUE)
			$scheme = $this->env->is_https ? 'https' : 'http';
		if ($subdomain)
			$host   = join('.', array_merge(
				(array)$subdomain, array($host)
			));
		if (isset($scheme))
			$host   = "${scheme}://${host}";

		return  $host;
	}

	public function home ($path = NULL) {

		$home = $this->conf->home	? $this->conf->home
						: getcwd();

		return join('/', array_merge(array($home), (array)$path));
	}

	public function pub  ($path = NULL) {

		$pub  = $this->conf->pub	? $this->conf->pub
						: preg_replace(
							'/\/[^\/]*$/', '',
							$this->env->script
						  );

		return join('/', array_merge(array($pub),  (array)$path));
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

	public function type () {

		if   (  $this->env->is_rewrite) {
			if (preg_match('/\.(\w+)$/', $this->env->uri, $match))
				$type = end($match);
		}
		else	$type = $this->args->type;

		if   (! isset($type))
			$type = $this->conf->type;

		return	strtolower($type);
	}

	public function render ($handler) {

		$type     = $this->type();
		$callback = $this->args->callback;

		if     (  isset($handler[$type]))
			$case = $handler[$type];
		elseif (  isset($handler[    0]))
			$case = $handler[    0];
		else
			return;

		if     (  $type == 'json' && $callback)
			$type =    'jsonp';

		if     (! headers_sent()) {

			if   (isset(self::$types[$type])) {

				$content_type = self::$types[$type];

				if ($this->conf->charset)
					$content_type   .= '; charset='
							.  $this->conf->charset;
			}
			else	$content_type = self::$types[0];

			header("Content-Type: ${content_type}");
			header('Expires: 0');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Pragma: no-cache');
		}

		$data     = is_object($case) && is_callable($case)
				? call_user_func($case, $this)
				:                $case;

		if     (  isset($data)) {

			if (preg_match('/^jsonp?$/', $type))
				$data = json_encode($data);
			if ($type == 'jsonp')
				$data = "${callback}(${data});";

			echo    $data;
		}

		return  TRUE;
	}

	public function redirect () {

		$location = call_user_func_array(
			array($this, 'uri'), func_get_args()
		);

		return $this->render(array(
			'json' => array('location' =>  $location),
			function() use ($location) {
				header ('Location: ' . $location, TRUE, 302);
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

			$route  = array_shift($this->routes);

			if   ($route->name())
				$this->routes[$route->name()] = $route;
			else
				$this->routes[]               = $route;
		}

		$done = 0;

		foreach ($this->routes as $route) {

			$to     = $route->to();
			$args   = $route->test($uri, $this->env->_data());

			if   (! $to || is_null($args)) continue;

			if   (  count($to) == 1 && is_callable($to[0]))
				$to      = array_shift($to);
			else {
				$target  = array_shift($to);
				$is_file = preg_match ('/[^\\\\\w]/', $target);

				if     ($to)
					$method = array_pop($to);
				elseif ($route->name())
					$method = strtr($route->name(), '-', '_');
				else
					$method = 'start';

				if     ($is_file) require_once $target;

				if     ($to)
					$class  = join('\\', $to);
				elseif ($is_file) {
					$info   = pathinfo($target);
					$class  = $info['filename'];
				}
				else	$class  = $target;

				$to      = array(new $class ($this), $method);
			}

			foreach ($args as $key => $val)
				$this->args->$key = $val;

			$result = call_user_func($to, $this);

			if   (  $result !== FALSE)	$done++;
			if   (  $result !== TRUE)	break;
		}

		return  $done;
	}

	public function is_valid ($val, $tests) {
		$queue = new Validator ($tests);
		return $queue->test($val);
	}

	public function sql_builder  () {
		return new SQLBuilder();
	}
}

?>
