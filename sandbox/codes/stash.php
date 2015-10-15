<?php

$lava->stash->foo = 'a';
$lava->stash->bar('b', 'c');

var_export(array(
	$lava->stash->foo,
	$lava->stash->foo(),
	$lava->stash->bar,	// последнее значение
	$lava->stash->bar(),	// все значения
));

?>
