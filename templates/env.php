<?php
	if (! isset($app)) exit;

	$key = $app->args->key;

	$app->stash->title = $app->dict()->tr('Env');

	include 'templates/_header.php';
?>
<div id="env" class="container">
	<div id="control">
		<a href="<?php echo $app->uri('index') ?>">
			<i class="fa fa-chevron-left" aria-hidden="true"></i>
			<?php echo $app->dict()->tr('To Home Page') ?>
		</a>
	</div>
	<?php if (preg_match('/^\w+$/', $key)): ?>
		<div class="row">
			<div class="col-sm-6">
				<div class="well">
					<h4 class="title ellipsis nowrap">lava->env-><?php echo $key ?></h4>
					<div class="essense">
<pre>
echo $app->env-><?php echo $key ?>;
# <?php echo htmlspecialchars(var_export($app->env->$key, TRUE)); ?>
</pre>
					</div>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="well">
					<h4 class="title ellipsis nowrap">lava->env-><?php echo $key ?>()</h4>
					<div class="essense">
<pre>
var_export($app->env-><?php echo $key ?>());
<?php echo preg_replace('/^/m', '# ', htmlspecialchars(var_export($app->env->$key(), TRUE))); ?>
</pre>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<div class="well">
		<h4 class="title ellipsis nowrap">lava->env</h4>
		<div class="essense table-responsive">
			<table class="table table-striped table-condensed">
				<?php foreach ($app->stash->env() as $key => $val): ?>
					<tr>
						<th>
							<a href="<?php echo $app->uri('env', array('key' => $key)) ?>"><?php echo htmlspecialchars($key) ?></a>
						</th>
						<td class="text-<?php echo is_string($val) ? 'muted' : 'info' ?>">
							<?php echo nl2br(htmlspecialchars(var_export($val, TRUE))) ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	</div>
</div>
<?php
	include 'templates/_footer.php';
?>
