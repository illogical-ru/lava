<?php

namespace Controller;

use App;


class Common {

    public function index () {
        App::render(['html' => function() {
            App::template('index.php');
        }]);
    }

    public function lang ($code) {
        App::cookie  ()->lang($code, '1Y', '/');
        App::redirect(preg_replace(
            ['/(\?|&)lang=[^&]*(?:&|$)/', '/\??&*$/'],
            ['\\1',                       ''],
            App::url_ref_or('')
        ));
    }

    public function env ($key) {
        App::render(['html' => function() use ($key) {
            App::template('env.php', ['key' => $key]);
        }]);
    }

    public function link () {
        App::render(['html' => function() {
            App::template('link.php');
        }]);
    }

    public function render () {
        App::render(['html' => function() {
            App::template('render.php');
        }]);
    }
    public function render_iframe () {
        App::render([
            'html' => 'HTML Content',
            'txt'  => function() {
                return 'Plain Text';
            },
            'json' => ['foo' => 123],
            function() {
                return 'Type: ' . App::type();
            },
        ]);
    }
}

?>
