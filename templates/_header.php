<?php
	if (! isset($app)) exit;

	$title   = $app->stash->title();
	$title[] = $app->dict()->tr('Lab');
?>
<!DOCTYPE html>
<html lang="<?php echo $app->lang(TRUE) ?>">
<head>
	<title><?php echo htmlspecialchars(join(' - ', preg_grep('/\S/', $title))) ?></title>
	<meta charset="<?php echo $app->conf->charset ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="<?php echo $app->pub('css/main.css') ?>" rel="stylesheet">
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
	<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
</head>
<body>
	<div id="content">
