<?php

namespace Controller;


class Common {

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

	// ссылки
	public function link () {
		$this->app->render(array('html' => function($app) {
			include 'templates/link.php';
		}));
	}

	// рендер
	public function render () {
		$this->app->render(array('html' => function($app) {
			include 'templates/render.php';
		}));
	}
	// рендер - фрэйм
	public function render_iframe () {
		$this->app->render(array(
			'html' => 'HTML Content',
			'txt'  => function() {
				return 'Plain text';
			},
			'json' => array('foo' => 123),
			function($app) {
				return 'Type: ' . $app->type();
			},
		));
	}
}

?>
