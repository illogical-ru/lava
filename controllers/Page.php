<?php

namespace Controller;


class Page {

	protected $app;


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

		$app = $this->app;

		// кладём в куки на год
		$app->cookie->lang($app->args->code, '1Y', '/');

		$app->redirect(preg_replace(
			array('/(\?|&)lang=[^&]*(?:&|$)/', '/\??&*$/'),
			array('\\1',                       ''),
			$app->url_ref_or('')
		));
	}

	// окружение
	public function env () {

		$app = $this->app;

		$app->stash->env = $app->env->_data();

		$app->render(array(
			'html' => function($app) {
				include 'templates/env.php';
			},
			'json' => $app->stash->env(),
		));
	}
}

?>
