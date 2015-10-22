lava-php
========

Micro-Framework

[Sandbox](http://lava.illogical.ru/)


## Конструктор


### new Lava\App [(config)] : lava

```
$app = new Lava\App (array(
    'charset' => 'utf-8',
    'home'    => '/path-to-home',
    'pub'     => '/pub-uri',
    'safe'    => array(
		'sign' => '',
		'algo' => 'md5',
		'salt' => '0123456789abcdef',
    ),
));
```


## Окружение


### lava->conf : context

Акцессор конфига

Как свойство отдает последнее значение

Как метод, список всех значений

```
$app->conf->foo = 123;
$app->conf->bar(4, 5);

var_export($app->conf->foo  ); # 123
var_export($app->conf->foo()); # array (0 => 123)
var_export($app->conf->bar  ); # 5
var_export($app->conf->bar()); # array (0 => 4, 1 => 5)
```

### lava->env : context

Переменные окружения

```
echo       $app->env->method;       # GET
var_export($app->env->accept());    # array (0 => 'text/html', 1 => '*/*')
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

### lava->uri([path|route [, data [, append]]]) : uri

Возвращает URI

Данные из data будут добавленны как query_string

Флаг append добавляет текущую query_string

```
# URL: http://example.com/sandbox/?zzz=456

echo $app->uri(),                           # /sandbox/
echo $app->uri('foo', array('bar' => 123)), # /sandbox/foo?bar=123
echo $app->uri('/foo', 'bar=123', TRUE),    # /foo?zzz=456&bar=123
```

### lava->url([path|route [, data [, append]]]) : url

Возвращает URL

```
# URL: http://example.com/sandbox/?zzz=456

echo $app->url(),                           # http://example.com/sandbox/
echo $app->url('foo', array('bar' => 123)), # http://example.com/sandbox/foo?bar=123
echo $app->url('/foo', 'bar=123', TRUE),    # http://example.com/foo?zzz=456&bar=123
```


## Маршруты


### lava->route(rule [, cond]) : route

Плейсхолдер `:name` соответствует полному фрагменту `([^\/]+)`

Плейсхолдер `#name` соответствует имени`([^\/]+?)(?:\.\w*)?`

Плейсхолдер `*name` соответствует оставшейся части `(.+)`

В дополнительных условиях `cond` можно добавить ограничение по переменным окружения `lava->env`

Если правило начинается не со слеша, то оно будет дополнено публичной папкой `lava->pub()`

```
$app  ->route('/:node1/#node2/*node3')
      ->to   (function($app) {				// обработчик
			echo $app->args->node1;			#  foo1.bar
			echo $app->args->node2;			#  foo2
			echo $app->args->node3;			#  foo3.bar/foo4.bar
      });

// поиск маршрута
$app->route_match('/foo1.bar/foo2.bar/foo3.bar/foo4.bar');


// ограничение по окружению
$app->route('/foo', array(
	'user_addr'  => '127.0.0.1',			// если пользователь локальный
	'method'     => array('GET', 'HEAD'),	// если метод GET или HEAD
	'user_agent' => '/^Mozilla/',			// если браузер Mozilla
));

// ограничение только по методу
$app->route('/foo', 'DELETE');
```

### lava->route_get(rule) : route

Аналог `$app->route('/foo', 'GET')`

### lava->route_post(rule) : route

Аналог `$app->route('/foo', 'POST')`

### lava->route_match([uri [, env]]) : void

Выполняет обработчики совпавших маршрутов

Если обработчик возвращает истинное значение, то продолжается проверка остальных в цепочке маршрутов

```
$app->route_match();		// будет использовано $app->env->uri
$app->route_match('/foo/bar');
$app->route_match('/foo', array('method' => 'POST');
```


## Рендеринг


### lava->render(handlers) : status

Выполняет обработчик соответствующего типа

```
$app->route('/page')->to(function($app) {
	$app->render(array(
		'html' => 'HTML CONTENT',
		'json' => array('bar' => 123),
		function ($app) {
			echo 'OTHER TYPE: ' . $app->type();
		},
	));
});

$app->route_match('/page.html');	# HTML CONTENT
$app->route_match('/page.json');	# {"bar":123}
$app->route_match('/page.xml');		# OTHER TYPE: xml
```

### lava->redirect([url|uri|route [, data [, append]]]) : void

Добавляет в заголовок `Location`

```
$app->redirect('/foo');
```


## Безопасность


### lava->safe->uuid() : uuid

Возвращает UUID

Указать алгоритм хеширования можно в конфиге, по умолчанию `md5`

```
echo $app->safe->uuid();	# 055fb982653fef1ae76bde78b10f7221

$foo = new Lava\App (array('safe' => array('algo' => 'sha256')));

echo $foo->safe->uuid();	# 49f2fbf757264416475e27e0ed7c56e89c69abc9efdd639ec6d6d2d4e521a8ea
```

### lava->safe->uuid_signed() : array(signed_uuid, uuid)

Возвращает подписанный UUID

Указать подпись можно в конфиге, по умолчанию пустая строка

```
list($signed, $uuid) = $app->safe->uuid_signed();

echo $signed;	# 31bd185d9b3929eb56ae6e4712b73962dcd6b2b55b5287117b9d65380f4146e3
echo $uuid;		# 31bd185d9b3929eb56ae6e4712b73962
```

### lava->safe->check(signed_uuid) : uuid

Проверяет подписанный UUID

```
echo $app->safe->check($signed);	# 31bd185d9b3929eb56ae6e4712b73962
```

### lava->safe->salt(size) : random_string

Возвращает случайную строку заданной длины

Изменить список доступных символов можно в конфиге, по умолчанию `0123456789abcdef`

```
echo $app->safe->salt(16);	# f8da4f571ec3de9d

$foo = new Lava\App (array('safe' => array('salt' => '01')));

echo $foo->safe->salt(16);	# 1001001110111100
```
