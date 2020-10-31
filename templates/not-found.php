<?php
    if (! isset($app)):
        exit;
    endif;

    $title = $app->dict()->tr('Not Found');

    $app->template('_header.php', ['title' => $title]);
?>
<div id="not-found" class="container">
    <div class="well">
        <h4 class="title ellipsis"><?php echo $title; ?></h4>
        <div class="essense">
            <p><a href="<?php echo $app->uri('index'); ?>"><?php echo $app->dict()->tr('To Home Page'); ?></a></p>
        </div>
    </div>
</div>
<?php
    $app->template('_footer.php');
?>
