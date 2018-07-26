<?php

use Lava\Stash;


class App extends Lava\App {

	private	$lang;

	static
	private $dict;


	// ------------------------------------------------------------------ //

	public function start () {

		$conf   = $this->conf;

		// языки
		$langs  = $conf->langs();
		// приоритет: квери, куки, браузер
		$accept = array_merge(
			$this->args->_get()->lang(),
			$this->cookie      ->lang(),
			array_reverse($this->env->accept_language()),
			array_keys         ($langs)
		);

		// добавляем короткие формы
		foreach (array_keys($langs) as $lang) {

			$langs[$lang] = $lang;
			$short = preg_replace('/-.+/', '', $lang);

			if (! isset($langs[$short]))
				$langs[$short] = $lang;
		}

		// ищем подходящий язык
		foreach ($accept as $lang)
			if (  isset($langs[$lang])) {
				$this->lang = $langs[$lang];
				break;
			}
	}

	// ------------------------------------------------------------------ //

	public function lang ($short = FALSE) {
		return $short	? preg_replace('/-.+/', '', $this->lang)
				:                           $this->lang;
	}

	public function dict ($name = 'main',  $lang = NULL) {

		$name .= '/' . (isset($lang) ? $lang : $this->lang());

		if (! isset(self::$dict[$name]))
			self::$dict[$name] = new Dict ("dict/${name}.php");

		return  self::$dict[$name];
	}

	// ------------------------------------------------------------------ //

	public function template ($file, $data = NULL) {

		$app = $this;

		if (   strpos($file, '/') !== 0)
			$file = "templates/${file}";

		if (! ($data instanceof Stash))
			$data = new Stash ($data);

		return include $file;
	}
}

?>
