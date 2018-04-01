<?php
	if (! isset($app)) exit;

	include 'templates/_header.php';
?>
<div id="index" class="container">
	<div class="row">
		<div class="col-sm-8">
			<div class="well">
				<h4 class="title ellipsis"><?php echo $app->dict()->tr('Hello, world') ?>!</h4>
				<div class="essense">
<pre>
require_once 'lib/Lava/Autoloader.php';

$al  = new Lava\Autoloader;

$al->extensions('php');
$al->register  ();

$app = new Lava\App (<?php echo htmlspecialchars(var_export($app->conf->_data(), TRUE)) ?>);
</pre>
				</div>
			</div>
		</div>
		<div class="col-sm-4">
			<div class="well">
				<h4 class="title ellipsis"><?php echo $app->dict()->tr('Useful') ?></h4>
				<div class="essense">
					<ul class="list-unstyled">
						<li>
							<a href="<?php echo $app->uri('env', array('key' => 'uri')) ?>">ENV</a>
							<a href="<?php echo $app->uri('env') ?>.json" target="_blank">
								<sup>JSON <i class="fa fa-external-link" aria-hidden="true"></i></sup>
							</a>
						</li>
						<li>
							<a href="<?php echo $app->uri('link', array('foo' => 123)) ?>"><?php echo $app->dict()->tr('Links') ?></a>
						</li>
						<li>
							<a href="<?php echo $app->uri('render') ?>"><?php echo $app->dict()->tr('Render') ?></a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	include 'templates/_footer.php';
?>
