<?php
    App::template('_header.php');
?>
<div id="index" class="container">
    <div class="row">
        <div class="col-sm-8">
            <div class="well">
                <h4 class="title ellipsis"><?php echo App::dict()->tr('Hello, world'); ?>!</h4>
                <div class="essense">
<pre>
require_once 'lib/Lava/Autoloader.php';

$al  = new Lava\Autoloader;

$al->extensions('php');
$al->register  ();

Lava::conf(<?php echo htmlspecialchars(var_export(App::conf()->_data(), TRUE)); ?>);
</pre>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="well">
                <h4 class="title ellipsis"><?php echo App::dict()->tr('Useful'); ?></h4>
                <div class="essense">
                    <ul class="list-unstyled">
                        <li>
                            <a href="<?php echo App::uri('env', ['key' => 'uri']); ?>">ENV</a>
                        </li>
                        <li>
                            <a href="<?php echo htmlspecialchars(App::uri('link', ['key_3' => 1, 'page' => 1])); ?>"><?php echo App::dict()->tr('Links'); ?></a>
                        </li>
                        <li>
                            <a href="<?php echo App::uri('render'); ?>"><?php echo App::dict()->tr('Render'); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    App::template('_footer.php');
?>
