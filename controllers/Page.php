<?php

namespace Controller;


class Page {

	public $app;


	public function __construct ($app) {
		$this->app = $app;
	}


	// главная страница
	public function index () {
		$this->app->render(array('html' => function($app) {
			include 'templates/index.php';
		}));
	}

	// язык
	public function lang () {

		$app  = $this->app;

		$code = $app->args->code;

		// кладём в куки на год
		$app->cookie->lang($code, '1Y', '/');

		$app->redirect(preg_replace(
			array('/(\?|&)lang=[^&]*(?:&|$)/', '/\??&*$/'),
			array('\\1',                       ''),
			$app->uri_ref_or('')
		));
	}
}

?>
