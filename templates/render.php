<?php

    if (!class_exists('App')) {
        exit;
    }

    App::template('_header.php', ['title' => App::dict()->tr('Render')]);
?>
<div id="render" class="container">
    <div class="well">
        <h4 class="title ellipsis nowrap">App::render(handlers) : has_handler</h4>
        <div class="essense">
            <div class="row">
                <div class="col-sm-6">
<pre>
App::render([
    'html' => 'HTML Content',
    'txt'  => function() {
        return 'Plain Text';
    },
    'json' => ['foo' => 123],
    // default
    function() {
        return 'Type: ' . App::type();
    },
]);
</pre>
                </div>
                <div class="col-sm-6">
                    <?php
                        foreach (['', 'html', 'txt', 'json', 'json?callback=bar', 'js'] as $type):
                            $url = App::url('render-item') . ($type ? ".${type}" : '');
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
    App::template('_footer.php');
?>
