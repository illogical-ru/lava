<?php
	if (! isset($app)) exit;

	include 'templates/_header.php';
?>
<div id="index" class="container">
	<div class="well">
		<h3 class="title"><?php echo $app->dict()->tr('Hello, world') ?>!</h3>
		<div class="essense">
			<ul class="list-unstyled">
				<li>
					<a href="<?php echo $app->uri('env', array('key' => 'uri')) ?>">ENV</a>
					<a href="<?php echo $app->uri('env') ?>.json" target="_blank">
						<sup>JSON <i class="fa fa-external-link" aria-hidden="true"></i></sup>
					</a>
				</li>
				<li>
					<a href="<?php echo $app->uri('link', array('foo' => 123)) ?>"><?php echo $app->dict()->tr('Link') ?></a>
				</li>
			</ul>
		</div>
	</div>
</div>
<?php
	include 'templates/_footer.php';
?>
