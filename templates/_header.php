<?php

    if (!class_exists('App')) {
        exit;
    }
?>
<!DOCTYPE html>
<html lang="<?php echo App::lang_short(); ?>">
<head>
    <title><?php echo htmlspecialchars(join(' - ', preg_grep('/\S/', array_merge($data->title(), [App::dict()->tr('Lab')])))); ?></title>
    <meta charset="<?php echo App::conf()->charset; ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="<?php echo App::pub('css/main.css'); ?>" rel="stylesheet">
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <div id="content">
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse" aria-expanded="false">
                        <span class="sr-only"><?php echo App::dict()->tr('Navigation'); ?></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a href="<?php echo App::uri('index'); ?>" class="navbar-brand">Lava</a>
                </div>
                <div class="collapse navbar-collapse" id="navbar-collapse">
                    <?php if (App::current_route_name() != 'index'): ?>
                        <ul class="nav navbar-nav">
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                                    <?php echo App::dict()->tr('Useful'); ?> <span class="caret"></span>
                                </a>
                                <?php App::template('_useful.php', ['class' => 'dropdown-menu']); ?>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
