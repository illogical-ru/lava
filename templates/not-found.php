<?php
	if (! isset($app)) exit;

	$app->stash->title = $app->dict()->tr('Not Found');

	include 'templates/_header.php';
?>
<div id="not-found" class="container">
	<h3><?php echo $app->dict()->tr('Not Found') ?></h3>
</div>
<?php
	include 'templates/_footer.php';
?>
