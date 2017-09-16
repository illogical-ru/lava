<?php

error_reporting(E_ALL);

// --- autoloader ----------------------------------------------------------- //

require_once 'lib/Lava/Autoloader.php';

$al  = new Lava\Autoloader;

$al->registerPrefixes(array(
	'Controller' => 'controllers',
));
$al->extensions      ('php');
$al->register        ();

// --- app ------------------------------------------------------------------ //

$app = new App (array(

	'type'     => 'html',
	'charset'  => 'UTF-8',

	'langs'    => array(
		'ru-RU' => 'Русский',
		'en-US' => 'English',
	),

	'timezone' => 'Europe/Moscow',
));


// кодировка
if (function_exists('mb_internal_encoding'))
	mb_internal_encoding($app->conf->charset);

// зона
date_default_timezone_set   ($app->conf->timezone);

// --- controllers ---------------------------------------------------------- //

// главная страница
$app	->route	()
	->name	('index')
	->to	('Controller\Page', 'index');

// язык
$app	->route	('lang/:code')
	->name	('lang')
	->to	('Controller\Page', 'lang');

// --- 404 ------------------------------------------------------------------ //

if (! $app->route_match())
	$app->render(array(
		'json' => array('error' => 'not-found'),
		function($app) {

			header('HTTP/1.0 404 Not Found');

			if ($app->type() == 'html')
				include 'templates/not-found.php';
		},
	));

?>
