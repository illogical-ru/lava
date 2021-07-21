<?php
    App::template('_header.php', ['title' => App::dict()->tr('Not Found')]);
?>
<div id="not-found" class="container">
    <div class="well">
        <h4 class="title ellipsis"><?php echo App::dict()->tr('Not Found'); ?></h4>
        <div class="essense">
            <p><a href="<?php echo App::uri('index'); ?>"><?php echo App::dict()->tr('To Home Page'); ?></a></p>
        </div>
    </div>
</div>
<?php
    App::template('_footer.php');
?>
