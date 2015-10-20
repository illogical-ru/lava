lava-php
========

Micro-Framework

[Sandbox](http://lava.illogical.ru/)

### new Lava\App [(config)] : lava

Конструктор

```
$app = new Lava\App (array(
    'charset' => 'utf-8',
    'home'    => '/path-to-home',
    'pub'     => '/pub-uri',
));
```

### lava->conf : context

Акцессор конфига

Как свойство отдает последнее значение

Как метод, список всех значений

```
$lava->conf->foo = 123;
$lava->conf->bar(4, 5);

var_export($lava->conf->foo  ); # 123
var_export($lava->conf->foo()); # array (0 => 123)
var_export($lava->conf->bar  ); # 5
var_export($lava->conf->bar()); # array (0 => 4, 1 => 5)
```

### lava->host([scheme]) : host

Возвращает хост

Если scheme равно TRUE, то текущая

```
echo $app->host(),      # host
echo $app->host(TRUE),	# http://host
echo $app->host('ftp'), # ftp://host
```

### lava->home([node, ...]) : home

Возвращает домашнюю папку

Если не установлена в конфиге, то текущую

```
echo $app->home(),              # /path-to-home
echo $app->home('foo', 'bar'),	# /path-to-home/foo/bar
```

### lava->pub([node, ...]) : pub

Возвращает публичную папку

Если не установлена в конфиге, то текущую

```
echo $app->pub(),             # /pub-uri
echo $app->pub('foo', 'bar'), # /pub-uri/foo/bar
```

### lava->uri([path [, data [, append]]]) : uri

Возвращает URI

Данные из data будут добавленны как query_string

Флаг append добавляет текущую query_string

```
# URI: /sandbox/?zzz=456

echo $app->uri(),                           # /sandbox/
echo $app->uri('foo', array('bar' => 123)), # /sandbox/foo?bar=123
echo $app->uri('/foo', 'bar=123', TRUE),    # /foo?zzz=456&bar=123
```

### lava->url([path [, data [, append]]]) : url

Возвращает URL

```
# URL: http://example.com/sandbox/?zzz=456

echo $app->url(),                           # http://example.com/sandbox/
echo $app->url('foo', array('bar' => 123)), # http://example.com/sandbox/foo?bar=123
echo $app->url('/foo', 'bar=123', TRUE),    # http://example.com/foo?zzz=456&bar=123
```

## Маршруты

### lava->route(rule [, conditionals]) : route

Плейсхолдер `:name` соответствует `[^\/]+`

Плейсхолдер `*name` соответствует `.+`

```
# URL: http://example.com/page/123

$app  ->route('/page/:id')
      ->name ('foo')                                      # имя маршрута
      ->to   (function($app) {                            # обработчик

          // получить переменные можно из $app->args
          $id = $app->args->id;                           # 123

          // получить URI или URL маршрута можно по имени
          echo $app->uri('foo', array('id' => $id + 1));  # /page/124
      });
```

### lava->route_get(rule) : route

Аналог lava->route(rule, array('method' => 'GET'))

### lava->route_post(rule) : route

Аналог lava->route(rule, array('method' => 'POST'))

### lava->route_match([uri]) : void

Выполняет обработчики совпавших маршрутов

Если обработчик возвращает истинное значение, то продолжается проверка остальных в цепочке маршрутов
