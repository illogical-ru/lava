<?php

var_export(array(
	$lava->uri(),
	$lava->uri('foo', array('bar' => 123)),
	$lava->uri('/foo', 'bar=123'),
));

?>
