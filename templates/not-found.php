<?php
	if (! isset($app)) exit;

	$app->stash->title = $app->dict()->tr('Not Found');

	include 'templates/_header.php';
?>
<div id="not-found" class="container">
	<h3><?php echo $app->stash->title ?></h3>
	<a href="<?php echo $app->uri('index') ?>"><?php echo $app->dict()->tr('Return to Home Page') ?></a>
</div>
<?php
	include 'templates/_footer.php';
?>
