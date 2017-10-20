<?php
	if (! isset($app)) exit;

	$app->stash->title = $app->dict()->tr('Not Found');

	include 'templates/_header.php';
?>
<div id="not-found" class="container">
	<div class="well">
		<h3 class="title ellipsis"><?php echo $app->stash->title ?></h3>
		<div class="essense">
			<p><a href="<?php echo $app->uri('index') ?>"><?php echo $app->dict()->tr('Return to Home Page') ?></a></p>
		</div>
	</div>
</div>
<?php
	include 'templates/_footer.php';
?>
