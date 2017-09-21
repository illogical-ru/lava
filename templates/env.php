<?php
	if (! isset($app)) exit;

	$app->stash->title = $app->dict()->tr('Env');

	include 'templates/_header.php';
?>
<div id="env" class="container">
	<h3><?php echo $app->stash->title ?></h3>
	<table class="table table-striped table-condensed">
		<?php foreach ($app->stash->env() as $key => $val): ?>
			<tr>
				<th>
					<?php echo htmlspecialchars($key) ?>
				</th>
				<td class="text-<?php echo is_string($val) ? 'muted' : 'info' ?>">
					<?php echo nl2br(htmlspecialchars(is_string($val) ? $val : var_export($val, TRUE))) ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<a href="<?php echo $app->uri('index') ?>"><?php echo $app->dict()->tr('Return to Home Page') ?></a>
</div>
<?php
	include 'templates/_footer.php';
?>
