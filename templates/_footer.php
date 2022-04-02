<?php

    if (!class_exists('App')) {
        exit;
    }
?>
    </div>
    <footer>
        <div class="container">
            <div id="langs" class="dropup pull-right">
                <button class="btn btn-sm btn-default dropdown-toggle" type="button" id="langs-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                    <i class="fa fa-globe" aria-hidden="true"></i>
                    <?php echo strtoupper(App::lang_short()); ?>
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="langs-dropdown">
                    <?php foreach (App::conf()->langs() as $code => $lang): ?>
                        <li class="<?php echo $code == App::lang() ? 'active' : ''; ?>">
                            <a href="<?php echo App::uri('lang', ['code' => $code]); ?>" rel="nofollow"><?php echo $lang; ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="small">
                <i class="fa fa-github" aria-hidden="true"></i>
                <a href="https://github.com/illogical-ru/lava" class="text-muted">
                    <?php echo App::dict()->tr('Powered by'); ?> Lava
                </a>
                <br />
            </div>
        </div>
    </footer>
    <script src="<?php echo App::pub('js/main.js'); ?>"></script>
</body>
</html>
