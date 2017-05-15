lava-php
========

Micro-Framework


## Конструктор


### new Lava\App ([conf]) : lava

```php
$app = new Lava\App (array(
    'charset' => 'utf-8',		// кодировка для HTTP заголовков
    'type'    => 'html',		// тип по умолчанию
    'home'    => '/path-to-home',	// домашняя папка
    'pub'     => '/pub-uri',		// публичная папка
    'safe'    => array(
	'sign' => '',			// подпись
	'algo' => 'md5',		// алгоритм хеширования
	'salt' => '0123456789abcdef',	// набор символов для соли
    ),
));
```


## Окружение


### lava->conf : context

Конфиг

```php
echo $app->conf->charset;	# utf-8
```

### lava->env : context

Окружение

```php
echo       $app->env->method;       # GET
var_export($app->env->accept());    # array (0 => 'text/html', 1 => '*/*')
```

### lava->args : context

Переменные

Приоритет значений: пользовательские, POST, GET

```php
// URL: http://example.com/sandbox/?foo=3&bar=4&foo=5

echo       $app->args->foo;       # 5
var_export($app->args->foo());    # array (0 => '3', 1 => '5')
```

### lava->cookie : context

Куки

Смещения для expire:

- s - секунда
- m - минута
- h - час
- D - день
- W - неделя
- M - месяц
- Y - год

```php
// установка
$app->cookie->foo = 'bar';
$app->cookie->bar = array(1, 2, 3);

// чтение
echo       $app->cookie->foo;		# bar
var_export($app->cookie->bar());	# array (0 => '1', 1 => '2', 2 => '3')

// дополнительные параметры: expire, path, domain, secure
$app->cookie->foo('bar', '1M');		// expire = 1 месяц
```

### lava->stash : context

Копилка

Как свойство отдает последнее значение, как метод, список всех значений

```php
$app->stash->foo = 123;
$app->stash->bar(4, 5);

var_export($app->stash->foo  ); # 123
var_export($app->stash->foo()); # array (0 => 123)
var_export($app->stash->bar  ); # 5
var_export($app->stash->bar()); # array (0 => 4, 1 => 5)
```


### lava->host([scheme [, subdomain]]) : host

Возвращает хост

Если scheme равно TRUE, то текущая

```php
echo $app->host(),      # host
echo $app->host(TRUE),	# http://host
echo $app->host('ftp'), # ftp://host
```

### lava->home([node, ...]) : home

Возвращает домашнюю папку

Если не установлена в конфиге, то текущую

```php
echo $app->home(),              # /path-to-home
echo $app->home('foo', 'bar'),	# /path-to-home/foo/bar
```

### lava->pub([node, ...]) : pub

Возвращает публичную папку

Если не установлена в конфиге, то текущую

```php
echo $app->pub(),             # /pub-uri
echo $app->pub('foo', 'bar'), # /pub-uri/foo/bar
```

### lava->uri([path|route [, data [, append]]]) : uri

Возвращает URI

Данные из data будут добавленны как query_string

Флаг append добавляет текущую query_string

```php
// URL: http://example.com/sandbox/?zzz=456

echo $app->uri(),                           # /sandbox/
echo $app->uri('foo', array('bar' => 123)), # /sandbox/foo?bar=123
echo $app->uri('/foo', 'bar=123', TRUE),    # /foo?zzz=456&bar=123
```

### lava->url([path|route [, data [, append]]]) : url

Возвращает URL

```php
// URL: http://example.com/sandbox/?zzz=456

echo $app->url(),                           # http://example.com/sandbox/
echo $app->url('foo', array('bar' => 123)), # http://example.com/sandbox/foo?bar=123
echo $app->url('/foo', 'bar=123', TRUE),    # http://example.com/foo?zzz=456&bar=123
```


## Маршруты


### lava->route([rule [, cond]]) : route

Плейсхолдер `:name` соответствует полному фрагменту `([^\/]+)`
Плейсхолдер `#name` соответствует имени`([^\/]+?)(?:\.\w*)?`
Плейсхолдер `*name` соответствует оставшейся части `(.+)`

Получение значения плейсхолдера `lava->args->name`

В дополнительных условиях `cond` можно добавить ограничение по переменным окружения

Если правило начинается не со слеша, то оно будет дополнено публичной папкой `lava->pub()`

```php
$app  ->route('/:node1/#node2/*node3')
      ->to   (function($app) {			// обработчик
		echo $app->args->node1;		#  foo1.bar
		echo $app->args->node2;		#  foo2
		echo $app->args->node3;		#  foo3.bar/foo4.bar
      });
// поиск маршрута
$app	->route_match('/foo1.bar/foo2.bar/foo3.bar/foo4.bar');

// ограничение по окружению
$app->route('/foo', array(
	'method'     => array('GET', 'HEAD'),	// если метод GET или HEAD
	'user_addr'  => '127.0.0.1',		// и пользователь локальный
	'user_agent' => '/^Mozilla/',		// и браузер Mozilla
));

// ограничение только по методу
$app->route('/foo', 'DELETE');
```

### lava->route_get([rule]) : route

Ограничить маршрут методом GET

```php
$app->route_get ('/foo');
// аналог
$app->route     ('/foo', 'GET');
```

### lava->route_post([rule]) : route

```php
$app->route_post('/foo');
```

### lava->route_match([uri [, env]]) : completed

Выполняет обработчики совпавших маршрутов

Если `uri` не указано, то будет использовано `lava->env->uri`
Если `env` не указано, то будет использовано `lava->env`

Если обработчик ничего не возвращает, то прекращается поиск маршрутов
Если обработчик возвращает `FALSE`, то прекращается поиск маршрутов, обработчик не считается как выполненный
Если обработчик возвращает `TRUE`, то продолжается проверка остальных в цепочке маршрутов

Возвращает количество выполненых обработчиков

```php
$app->route_match();		// будет использовано $app->env->uri
$app->route_match('/foo/bar');
$app->route_match('/foo', array('method' => 'POST');
```

### route->cond(cond) : route

Добавить к маршруту ограничение по окружению

```php
$app	->route('/foo')
	->cond (array('user_addr' => '/^192\.168\./'));
```

### route->name(name) : route

Служит для преобразования маршрута в путь

```php
$app	->route('/foo/#id')
	->name ('bar')
	->to   (function($app) {
		$id = $app->args->id;				//      123
		echo $app->uri('bar', array('id' => $id + 1));	#  /foo/124
	});

$app	->route_match('/foo/123');
```

### route->to(mixed) : route

Обработчик маршрута

Обработчик получает в качестве аргумента `lava`

```php
// функция
$app->route('/foo')->to(function() {echo 'hello';});

// файл, метод
$app->route('/foo')->to('controller/Foo.php', 'bar');
// имя класса должно совпадать с именем файла
// будет создан экземпляр класса Foo и вызван метод bar

// файл, класс|неймспейс, метод
$app->route('/foo')->to('controller/Foo.php', 'Ctrl\Foo', 'bar');
// если класс отличается от имени файла или нужно указать неймспейс
```


## Рендеринг


### lava->render(handlers) : has_handler

Выполняет обработчик с типом `lava->type()`, если не существует, то с индексом `0`

Если нет типа запрашиваемых данных, то используется `lava->conf->type`

Если тип `json` и есть значение `lava->args->callback`, возвращает `JSONP`

```php
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

// если lava->conf->type == 'html'
$app->route_match('/page');		# HTML CONTENT
```

### lava->redirect([url|uri|route [, data [, append]]]) : void

Добавляет в заголовок `Location`

```php
$app->redirect('/foo');
```


## Безопасность


### lava->safe->uuid() : uuid

Возвращает UUID

Указать алгоритм хеширования можно в конфиге, по умолчанию `md5`

```php
echo $app->safe->uuid();	# 055fb982653fef1ae76bde78b10f7221

$foo = new Lava\App (array('safe' => array('algo' => 'sha256')));

echo $foo->safe->uuid();	# 49f2fbf757264416475e27e0ed7c56e89c69abc9efdd639ec6d6d2d4e521a8ea
```

### lava->safe->uuid_signed() : array(signed_uuid, uuid)

Возвращает подписанный UUID

Указать подпись можно в конфиге, по умолчанию пустая строка

```php
$foo = new Lava\App (array('safe' => array('sign' => 'random_string')));

list($signed, $uuid) = $foo->safe->uuid_signed();

echo $signed;		# 31bd185d9b3929eb56ae6e4712b73962dcd6b2b55b5287117b9d65380f4146e3
echo $uuid;		# 31bd185d9b3929eb56ae6e4712b73962
```

### lava->safe->check(signed_uuid) : uuid

Проверяет подписанный UUID

```php
echo $app->safe->check($signed);	# 31bd185d9b3929eb56ae6e4712b73962
```

### lava->safe->salt(size) : random_string

Возвращает случайную строку заданной длины

Изменить список доступных символов можно в конфиге, по умолчанию `0123456789abcdef`

```php
echo $app->safe->salt(16);	# f8da4f571ec3de9d

$foo = new Lava\App (array('safe' => array('salt' => '01')));

echo $foo->safe->salt(16);	# 1001001110111100
```
