<?php

require '../lib/Lava.php';

$lava  = new Lava\App (array('charset' => 'utf-8'));
$args  = $lava->args;

$codes = array();

if ($d = opendir('codes')) {
	while  (($f = readdir($d)) !== FALSE)
		if (preg_match('/\.php$/', $f)) $codes[] = $f;
	closedir($d);
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="<?php echo $lava->conf->charset ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>sandbox | lava-php</title>
	<style>
		body {
			padding: 1em;
			font-family: Helvetica, Arial, sans-serif;
		}

		header {
			padding-bottom: 1em;
			color: #777;
			text-align: center;
			border-bottom: 1px dashed #ccc;
		}
		footer {
			margin-top: 1em;
			text-align: center;
		}

		a {
			color: #5be;
			text-decoration: none;
		}

		pre {
			padding: 1em;
			overflow: auto;
			background: #eee;
			border-left: 4px solid #6cf;
			font-size: 90%;
		}

		h1 {
			font-size: 120%;
		}

		.label {
			padding: 4px 8px;
			background: #5b5;
			font-size: 70%;
			line-height: 210%;
			border-radius: 5px;
			color: #fff;
		}

		.label.active {
			background: #393;
		}

		.block {
			padding-bottom: 1em;
			border-bottom: 1px dashed #ccc;
		}
	</style>
</head>
<body>
	<header>
		<h1>sandbox</h1>
	</header>
	<?php foreach ($codes as $code): ?>
		<div class="block">
			<p><?php echo $code ?></p>
			<pre><?php

				echo htmlspecialchars(preg_replace(
					'/^\s*<\?php\s*|\s*\?>\s*$/', '',
					file_get_contents("codes/${code}")
				));

				ob_start();
				include "codes/${code}";
				$result = ob_get_contents();
				ob_end_clean();
				echo "\n\n<small>";
				echo preg_replace('/^/m', '// ', $result);
				echo "</small>";

			?></pre>
		</div>
	<?php endforeach; ?>
	<footer><a href="https://github.com/illogical-ru/lava-php"><strong>github.com</strong>/illogical-ru/lava-php</a></footer>
</body>
</html>
