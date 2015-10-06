<?php

list($signed, $uuid) = $lava->safe->uuid_signed();

var_export(array(
	$signed,
	$uuid,
	$lava->safe->check($signed),
));

?>
