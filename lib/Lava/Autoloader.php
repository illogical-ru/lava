<?php

/*!
 * https://github.com/illogical-ru/lava-php
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;

// PHP >= 5.3
if (version_compare(phpversion(), '5.3') < 0)
	die('PHP 5.3+ is required');


class Autoloader {

	private $include    = array(),
		$extensions = array(),
		$prefixes   = array();


	public function __construct () {

		// include

		$this->include[] = substr(
			__DIR__, 0, strrpos(__DIR__, DIRECTORY_SEPARATOR)
		);

		foreach (explode(PATH_SEPARATOR, get_include_path()) as $path)
			if (preg_match('/(\S+(?:.*\S)?)/',  $path, $match))
				$this->include[]    = $match[1];

		// extensions

		foreach (explode(',', spl_autoload_extensions())     as $ext)
			if (preg_match('/^\s*\.(\S+)\s*$/', $ext,  $match))
				$this->extensions[] = $match[1];
	}


	public function register   ($prepend = FALSE) {
		return spl_autoload_register  (
			array($this, 'load'), TRUE, $prepend
		);
	}
	public function unregister () {
		return spl_autoload_unregister(
			array($this, 'load')
		);
	}

	public function registerPrefix   ($name, $paths) {
		$this->prefixes[$name] = (array) $paths;
	}
	public function registerPrefixes (array  $data) {
		foreach ($data as $name => $paths)
			$this->registerPrefix($name, $paths);
	}

	public function extensions ($ext = NULL) {
		if (isset($ext))
			$this->extensions = (array)$ext;
		return  $this->extensions;
	}

	public function find ($class) {

		$include    = $this->include;
		$separator  = DIRECTORY_SEPARATOR;
		$is_ns      = strpos($class, '\\') !== FALSE;

		foreach ($this->prefixes as $prefix => $paths) {

			if ($is_ns)				$prefix .= '\\';

			if (strpos($class, $prefix) !== 0)	continue;

			$class   = substr($class, strlen($prefix));

			$count   = count ($include);

			foreach ($paths as $path)
				if   (strpos($path, $separator) === 0)
						$include[] =	  $path;
				else
					for ($i = 0; $i < $count; $i++)
						$include[] =	  $include[$i]
								. $separator
								. $path;

			$include = array_splice($include, $count);

			break;
		}

		$class_path = strtr($class, $is_ns ? '\\' : '_', $separator);

		foreach ($include as $path) {

			$file = $path . $separator . $class_path;

			foreach ($this->extensions as $ext)
				if (file_exists("${file}.${ext}"))
					return  "${file}.${ext}";
		}
	}

	public function load ($class) {
		$file = $this->find($class);
		if ($file) return require $file;
	}
}

?>
