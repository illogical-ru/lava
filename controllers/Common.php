<?php

namespace Controller;


class Common {

    protected $app;


    public function __construct ($app) {
        $this->app = $app;
    }


    // главная страница
    public function index () {
        $this->app->render(['html' => function($app) {
            $app->template('index.php');
        }]);
    }

    // язык
    public function lang () {

        $app = $this->app;

        // кладём в куки на год
        $app->cookie->lang($app->args->code, '1Y', '/');

        $app->redirect(preg_replace(
            ['/(\?|&)lang=[^&]*(?:&|$)/', '/\??&*$/'],
            ['\\1',                       ''],
            $app->url_ref_or('')
        ));
    }

    // окружение
    public function env () {

        $app = $this->app;

        $app->stash->env = $app->env->_data();

        $app->render([
            'html' => function($app) {
                $app->template('env.php');
            },
            'json' => $app->stash->env(),
        ]);
    }

    // ссылки
    public function link () {
        $this->app->render(['html' => function($app) {
            $app->template('link.php');
        }]);
    }

    // рендер
    public function render () {
        $this->app->render(['html' => function($app) {
            $app->template('render.php');
        }]);
    }
    // рендер - фрэйм
    public function render_iframe () {
        $this->app->render([
            'html' => 'HTML Content',
            'txt'  => function() {
                return 'Plain Text';
            },
            'json' => ['foo' => 123],
            function($app) {
                return 'Type: ' . $app->type();
            },
        ]);
    }
}

?>
