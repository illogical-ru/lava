<?php

error_reporting(E_ALL);

// --- автолоадер ----------------------------------------------------------- //

require_once 'lib/Lava/Autoloader.php';

$al   = new Lava\Autoloader;

$al->register_prefixes([
    'Controller' => 'controllers',
]);
$al->extensions       ('php');
$al->register         ();

// --- приложение ----------------------------------------------------------- //

$conf = App::conf(require_once 'conf.php');


// кодировка
if ($conf->charset && function_exists('mb_internal_encoding')) {
    mb_internal_encoding     ($conf->charset);
}
// зона
if ($conf->timezone) {
    date_default_timezone_set($conf->timezone);
}

// --- контроллёры ---------------------------------------------------------- //

// главная страница
App ::route()
    ->name ('index')
    ->to   ('Controller\Common');

// язык
App ::route('lang/:code')
    ->name ('lang')
    ->to   ('Controller\Common', 'lang');

// окружение
App ::route('env/#key')
    ->name ('env')
    ->to   ('Controller\Common', 'env');

// ссылки
App ::route('link')
    ->name ('link')
    ->to   ('Controller\Common', 'link');

// рендер
App ::route('render')
    ->name ('render')
    ->to   ('Controller\Common', 'render');
// рендер - фрэйм
App ::route('render/item')
    ->name ('render-item')
    ->to   ('Controller\Common', 'render_iframe');

// --- 404 ------------------------------------------------------------------ //

if (!App::routes_match()) {
    App::render([
        'json' => ['error' => 'not-found'],
        function() {

            header('HTTP/1.0 404 Not Found');

            if (App::type() == 'html') {
                App::template('not-found.php');
            }
        },
    ]);
}

?>
