<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;

use Lava\Stash;
use Lava\Date;


class Cookie extends Stash {

	public function __construct () {
		parent::__construct($_COOKIE);
	}


	public function __set ($key, $val) {

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
		if   ($args)
			return call_user_func_array(
				array      ($this, '__set'),
				array_merge(array($key), $args)
			);
		else
			return parent::__call($key, $args);
	}

	public function __unset ($key) {

		$data = $this->_data();

		if     (! isset   ($data[$key]))
			return;
		elseif (  is_array($data[$key])) {
			array_walk_recursive(
				$data[$key], function(&$item) {$item = NULL;}
			);
			$this->$key = $data[$key];
		}
		else	$this->$key = NULL;
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

?>
