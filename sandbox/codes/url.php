<?php

var_export(array(
	$lava->url(),
	$lava->url('foo', array('bar' => 123)),
	$lava->url('/foo', 'bar=123'),
));

?>
