<?php
	if (! isset($app)) exit;

	include 'templates/_header.php';
?>
<div id="index" class="container">
	<h3><?php echo $app->dict()->tr('Hello, world') ?>!</h3>
	<ul class="list-unstyled">
		<li>
			<a href="<?php echo $app->uri('env') ?>">ENV</a>
			<a href="<?php echo $app->uri('env') ?>.json" target="_blank">
				<sup>JSON <i class="fa fa-external-link" aria-hidden="true"></i></sup>
			</a>
		</li>
	</ul>
</div>
<?php
	include 'templates/_footer.php';
?>
