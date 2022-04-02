<?php

error_reporting(E_ALL);

// --- Autoloader ----------------------------------------------------------- //

require_once 'lib/Lava/Autoloader.php';

$al   = new Lava\Autoloader;

$al->register_prefixes([
    'Controller' => 'controllers',
]);
$al->extensions       ('php');
$al->register         ();

// --- Conf ----------------------------------------------------------------- //

$conf = App::conf(require_once 'conf.php');


if ($conf->charset && function_exists('mb_internal_encoding')) {
    mb_internal_encoding     ($conf->charset);
}
if ($conf->timezone) {
    date_default_timezone_set($conf->timezone);
}

// --- Controllers ---------------------------------------------------------- //

App ::route()
    ->name ('index')
    ->to   ('Controller\Common');

App ::route('lang/:code')
    ->name ('lang')
    ->to   ('Controller\Common', 'lang');

App ::route('env/#key')
    ->name ('env')
    ->to   ('Controller\Common', 'env');

App ::route('links')
    ->name ('links')
    ->to   ('Controller\Common', 'links');

App ::route('render')
    ->name ('render')
    ->to   ('Controller\Common', 'render');
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
