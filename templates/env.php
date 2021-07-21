<?php

    $key = $data->key;

    App::template('_header.php', ['title' => App::dict()->tr('Environment')]);
?>
<div id="env" class="container">
    <div id="control">
        <a href="<?php echo App::uri('index'); ?>">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
            <?php echo App::dict()->tr('To Home Page'); ?>
        </a>
    </div>
    <?php if (preg_match('/^\w+$/', $key)): ?>
        <div class="row">
            <div class="col-sm-6">
                <div class="well">
                    <h4 class="title ellipsis nowrap">Lava::env()-><?php echo $key; ?></h4>
                    <div class="essense">
                        <pre><?php echo htmlspecialchars(var_export(App::env()->$key, TRUE)); ?></pre>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="well">
                    <h4 class="title ellipsis nowrap">Lava::env()-><?php echo $key; ?>()</h4>
                    <div class="essense">
                        <pre><?php echo htmlspecialchars(var_export(App::env()->$key(), TRUE)); ?></pre>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div class="well">
        <h4 class="title ellipsis nowrap"><?php echo App::dict()->tr('Environment') ?></h4>
        <div class="essense table-responsive">
            <table class="table table-striped table-condensed">
                <?php foreach (App::env()->_data() as $key => $val): ?>
                    <tr>
                        <th>
                            <a href="<?php echo App::uri('env', ['key' => $key]); ?>"><?php echo htmlspecialchars($key); ?></a>
                        </th>
                        <td class="text-<?php echo is_string($val) ? 'muted' : 'info'; ?>">
                            <?php echo nl2br(htmlspecialchars(var_export($val, TRUE))); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<?php
    App::template('_footer.php');
?>
