<?php
    if (! isset($app)):
        exit;
    endif;

    $app->template('_header.php', ['title' => $app->dict()->tr('Render')]);
?>
<div id="render" class="container">
    <div id="control">
        <a href="<?php echo $app->uri('index'); ?>">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
            <?php echo $app->dict()->tr('To Home Page'); ?>
        </a>
    </div>
    <div class="well">
        <h4 class="title ellipsis nowrap">lava->render(handlers) : has_handler</h4>
        <div class="essense">
            <div class="row">
                <div class="col-sm-6">
<pre>
$app->render([
    'html' => 'HTML Content',
    'txt'  => function() {
        return 'Plain Text';
    },
    'json' => ['foo' => 123],
    // default
    function($app) {
        return 'Type: ' . $app->type();
    },
]);
</pre>
                </div>
                <div class="col-sm-6">
                    <?php
                        foreach (['', 'html', 'txt', 'json', 'json?callback=bar', 'js'] as $type):
                            $url = $app->url('render-item') . ($type ? ".${type}" : '');
                    ?>
                            <div class="ellipsis"><a href="<?php echo $url; ?>" target="_blank"><?php echo $url; ?></a></div>
                            <iframe src="<?php echo $url; ?>" height="35"></iframe>
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
    $app->template('_footer.php');
?>
