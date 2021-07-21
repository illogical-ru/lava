<?php
    App::template('_header.php', ['title' => App::dict()->tr('Links')]);
?>
<div id="link" class="container">
    <div id="control">
        <a href="<?php echo App::uri('index'); ?>">
            <i class="fa fa-chevron-left" aria-hidden="true"></i>
            <?php echo App::dict()->tr('To Home Page'); ?>
        </a>
    </div>
    <div class="well">
        <h4 class="title ellipsis nowrap">Lava::uri(NULL, data, TRUE) : uri</h4>
        <div class="essense">
            <div class="row text-center">
                <div class="col-sm-6">
                    <?php
                        for ($i = 1; $i <= 5; $i++):

                            $key = "key_${i}";
                    ?>
                            <a href="<?php echo htmlspecialchars(App::uri(NULL, [$key => !App::args()->$key], TRUE)); ?>" class="btn btn-xs btn-<?php echo App::args()->$key ? 'info' : 'default'; ?>">
                                <?php echo htmlspecialchars($key . '=' . App::args()->$key); ?>
                            </a>
                    <?php
                        endfor;
                    ?>
                </div>
                <div class="col-sm-6">
                    <?php
                        for ($i = 1; $i <= 5; $i++):
                    ?>
                            <a href="<?php echo htmlspecialchars(App::uri(NULL, ['page' => $i], TRUE)); ?>" class="btn btn-xs btn-<?php echo $i == App::args()->page ? 'primary' : 'default'; ?>">
                                page=<?php echo $i; ?>
                            </a>
                    <?php
                        endfor;
                    ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <div class="well">
                <h4 class="title ellipsis nowrap">Lava::uri([path|route [, data [, append]]]) : uri</h4>
                <div class="essense">
<pre>
echo Lava::uri();
# <?php echo htmlspecialchars(var_export(App::uri(), TRUE)); ?>
</pre>

<pre>
echo Lava::uri('');
# <?php echo htmlspecialchars(var_export(App::uri(''), TRUE)); ?>
</pre>

<pre>
echo Lava::uri('bar');
# <?php echo htmlspecialchars(var_export(App::uri('bar'), TRUE)); ?>
</pre>

<pre>
echo Lava::uri('/bar', ['arg' => '1#2']);
# <?php echo htmlspecialchars(var_export(App::uri('/bar', ['arg' => '1#2']), TRUE)); ?>
</pre>

<pre>
echo Lava::uri('bar', 'arg=1#2', TRUE);
# <?php echo htmlspecialchars(var_export(App::uri('bar', 'arg=1#2', TRUE), TRUE)); ?>
</pre>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="well">
                <h4 class="title ellipsis nowrap">Lava::url([path|route [, data [, append [, subdomain]]]]) : url</h4>
                <div class="essense">
<pre>
echo Lava::url();
# <?php echo htmlspecialchars(var_export(App::url(), TRUE)); ?>
</pre>

<pre>
echo Lava::url('');
# <?php echo htmlspecialchars(var_export(App::url(''), TRUE)); ?>
</pre>

<pre>
echo Lava::url('bar');
# <?php echo htmlspecialchars(var_export(App::url('bar'), TRUE)); ?>
</pre>

<pre>
echo Lava::url('/bar', ['arg' => '1#2']);
# <?php echo htmlspecialchars(var_export(App::url('/bar', ['arg' => '1#2']), TRUE)); ?>
</pre>

<pre>
echo Lava::url('bar', 'arg=1#2', TRUE);
# <?php echo htmlspecialchars(var_export(App::url('bar', 'arg=1#2', TRUE), TRUE)); ?>
</pre>

<pre>
echo Lava::url('bar', 'arg=1#2', TRUE, 'subdomain');
# <?php echo htmlspecialchars(var_export(App::url('bar', 'arg=1#2', TRUE, 'subdomain'), TRUE)); ?>
</pre>

                </div>
            </div>
        </div>
    </div>
    <div class="well">
        <h4 class="title ellipsis nowrap">Lava::host([scheme [, subdomain]]) : host</h4>
        <div class="essense">
<pre>
echo Lava::host();
# <?php echo htmlspecialchars(var_export(App::host(), TRUE)); ?>
</pre>

<pre>
echo Lava::host('ftp');
# <?php echo htmlspecialchars(var_export(App::host('ftp'), TRUE)); ?>
</pre>

<pre>
echo Lava::host(TRUE);
# <?php echo htmlspecialchars(var_export(App::host(TRUE), TRUE)); ?>
</pre>

<pre>
echo Lava::host('https', 'safe');
# <?php echo htmlspecialchars(var_export(App::host('https', 'safe'), TRUE)); ?>
</pre>
        </div>
    </div>
    <div class="well">
        <h4 class="title ellipsis nowrap">Lava::pub([node, ...]) : path</h4>
        <div class="essense">
<pre>
echo Lava::pub();
# <?php echo htmlspecialchars(var_export(App::pub(), TRUE)); ?>
</pre>

<pre>
echo Lava::pub('js/main.js');
# <?php echo htmlspecialchars(var_export(App::pub('js/main.js'), TRUE)); ?>
</pre>
        </div>
    </div>
</div>
<?php
    App::template('_footer.php');
?>
