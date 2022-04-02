<?php

    if (!class_exists('App')) {
        exit;
    }

    $list = [
        'env'    => [
            'args' => [
                'key'   => 'uri',
            ],
        ],
        'links'  => [
            'args' => [
                'key_3' => 1,
                'page'  => 1,
            ],
        ],
        'render' => [],
    ];
?>
<ul class="<?php echo $data->class ? $data->class : 'list-unstyled'; ?>">
    <?php foreach ($list as $key => $opts): ?>
        <li class="<?php echo App::current_route_name() == $key ? 'active' : ''; ?>">
            <a href="<?php echo htmlspecialchars(App::uri($key, isset($opts['args']) ? $opts['args'] : NULL)); ?>">
                <?php echo App::dict()->tr(isset($opts['name']) ? $opts['name'] : ucfirst($key)); ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
