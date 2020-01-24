<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Safe {

	private $id   =  0,
		$sign = '',
		$algo = 'md5',
		$salt = '0123456789abcdef';


	public function __construct ($opts = NULL) {

		foreach (array('sign', 'algo', 'salt') as $key)
			if (isset($opts[$key])) $this->$key = $opts[$key];

		$this->salt = preg_split(
			'//u', $this->salt, -1, PREG_SPLIT_NO_EMPTY
		);
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

		$salt   = '';
		$mt_max = count($this->salt) - 1;

		while  ($size-- > 0)
			$salt .= $this->salt[mt_rand(0, $mt_max)];

		return  $salt;
	}


	private function _hash () {
		return hash($this->algo, join(':', func_get_args()));
	}
}

?>
