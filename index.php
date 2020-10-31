<?php

error_reporting(E_ALL);

// --- автолоадер ----------------------------------------------------------- //

require_once 'lib/Lava/Autoloader.php';

$al  = new Lava\Autoloader;

$al->registerPrefixes([
    'Controller' => 'controllers',
]);
$al->extensions      ('php');
$al->register        ();

// --- приложение ----------------------------------------------------------- //

$app = new App (require 'conf.php');

// кодировка
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding ($app->conf->charset);
}

// зона
date_default_timezone_set($app->conf->timezone);

// --- контроллёры ---------------------------------------------------------- //

// главная страница
$app->route()
    ->name ('index')
    ->to   ('Controller\Common', 'index');

// язык
$app->route('lang/:code')
    ->name ('lang')
    ->to   ('Controller\Common', 'lang');

// окружение
$app->route('env/:key')
    ->name ('env')
    ->to   ('Controller\Common', 'env');

// ссылки
$app->route('link')
    ->name ('link')
    ->to   ('Controller\Common', 'link');

// рендер
$app->route('render')
    ->name ('render')
    ->to   ('Controller\Common', 'render');
// рендер - фрэйм
$app->route('render/item')
    ->name ('render-item')
    ->to   ('Controller\Common', 'render_iframe');

// --- 404 ------------------------------------------------------------------ //

if (! $app->route_match()) {
    $app->render([
        'json' => ['error' => 'not-found'],
        function($app) {

            header('HTTP/1.0 404 Not Found');

            if ($app->type() == 'html') {
                $app->template('not-found.php');
            }
        },
    ]);
}

?>
