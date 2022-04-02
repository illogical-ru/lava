<?php

    if (!class_exists('App')) {
        exit;
    }

    App::template('_header.php', ['title' => App::dict()->tr('Not Found')]);
?>
<div id="not-found" class="container">
    <div class="well">
        <div class="essense">
            <p class="text-center text-danger"><strong><?php echo App::dict()->tr('Not Found'); ?></strong></p>
        </div>
    </div>
</div>
<?php
    App::template('_footer.php');
?>
