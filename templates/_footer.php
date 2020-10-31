<?php
    if (! isset($app)):
        exit;
    endif;
?>
    </div>
    <footer>
        <div class="container">
            <div id="langs" class="dropup pull-right">
                <button class="btn btn-sm btn-default dropdown-toggle" type="button" id="langs-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <i class="fa fa-globe" aria-hidden="true"></i>
                    <?php echo strtoupper($app->lang(TRUE)); ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="langs-dropdown">
                    <?php foreach ($app->conf->langs() as $code => $lang): ?>
                        <li class="<?php echo $code == $app->lang() ? 'active' : ''; ?>"><a href="<?php echo $app->uri('lang', ['code' => $code]); ?>" rel="nofollow"><?php echo $lang; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="small">
                <i class="fa fa-github" aria-hidden="true"></i>
                <a href="https://github.com/illogical-ru/lava-php" class="text-muted"><?php echo $app->dict()->tr('Powered by'); ?> Lava</a><br>
            </div>
        </div>
    </footer>
    <script src="<?php echo $app->pub('js/main.js'); ?>"></script>
</body>
</html>
