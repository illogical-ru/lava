<?php
	if (! isset($app)) exit;

	include 'templates/_header.php';
?>
<div id="index" class="container">
	<h3><?php echo $app->dict()->tr('Hello, world') ?>!</h3>
</div>
<?php
	include 'templates/_footer.php';
?>
