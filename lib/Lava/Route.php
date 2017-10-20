<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


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

		if     (! preg_match('/^\/*$/', end($segs)))
				$regexp[] = '(?:\.\w*)?';

		$this->segs   = array_values($segs);
		$this->regexp = sprintf     ('/^%s$/', join('', $regexp));

		if     (  is_string($cond))	$this->cond['method'] = $cond;
		elseif (  is_array ($cond))	$this->cond           = $cond;
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

	public function uri (&$args = NULL) {

		$uri = array();

		foreach ($this->segs as $i => $seg) {

			$uri[] = $i & 1 && isset($args[$seg])	? $args[$seg]
								:       $seg;
			unset($args[$seg]);
		}

		return  join('', $uri);
	}
}

?>
