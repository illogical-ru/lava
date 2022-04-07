lava
========

Micro-Framework

Требования: PHP 5.4+


## Установка


```bash
composer require illogical/lava
```


## Окружение


### Lava::conf([data]) : accessor

Конфиг

```php
Lava::conf([
    'charset' => 'utf-8',             // кодировка для HTTP заголовков
    'type'    => 'html',              // тип по умолчанию
    'home'    => '/path-to-home',     // домашняя папка
    'pub'     => '/pub-uri',          // публичная папка
    'safe'    => [
        'sign' => '',                 // подпись
        'algo' => 'md5',              // алгоритм хеширования
        'salt' => '0123456789abcdef', // набор символов для соли
    ],
]);

echo Lava::conf()->charset; # utf-8
```

### Lava::env() : accessor

Окружение

```php
echo       Lava::env()->method;    # GET
var_export(Lava::env()->accept()); # array (0 => 'text/html', 1 => '*/*')
```

### Lava::args() : accessor

Переменные

Приоритет значений: пользовательские, POST, GET

```php
// URL: http://example.com/sandbox/?foo=3&bar=4&foo=5

echo       Lava::args()->foo;    # 5
var_export(Lava::args()->foo()); # array (0 => '3', 1 => '5')
```

### Lava::cookie() : accessor

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
Lava::cookie()->foo = 'bar';
Lava::cookie()->bar = [1, 2, 3];

// чтение
echo       Lava::cookie()->foo;    # bar
var_export(Lava::cookie()->bar()); # array (0 => '1', 1 => '2', 2 => '3')

// дополнительные параметры: expire, path, domain, secure
Lava::cookie()->foo('bar', '1M');  // expire = 1 месяц
```


### Lava::host([scheme [, subdomain]]) : host

Возвращает хост

Если scheme равно TRUE, то текущая

```php
echo Lava::host();      # host
echo Lava::host(TRUE);  # http://host
echo Lava::host('ftp'); # ftp://host
```

### Lava::home([node, ...]) : home

Возвращает домашнюю папку

Если не установлена в конфиге, то текущую

```php
echo Lava::home();             # /path-to-home
echo Lava::home('foo', 'bar'); # /path-to-home/foo/bar
```

### Lava::pub([node, ...]) : pub

Возвращает публичную папку

Если не установлена в конфиге, то текущую

```php
echo Lava::pub();             # /pub-uri
echo Lava::pub('foo', 'bar'); # /pub-uri/foo/bar
```

### Lava::uri([path|route [, data [, append]]]) : uri

Возвращает URI

Данные из data будут добавленны как query_string

Флаг append добавляет текущую query_string

```php
// URL: http://example.com/sandbox/?zzz=456

echo Lava::uri();                        # /sandbox/
echo Lava::uri('foo', ['bar' => 123]);   # /sandbox/foo?bar=123
echo Lava::uri('/foo', 'bar=123', TRUE); # /foo?zzz=456&bar=123
```

### Lava::url([path|route [, data [, append]]]) : url

Возвращает URL

```php
// URL: http://example.com/sandbox/?zzz=456

echo Lava::url();                        # http://example.com/sandbox/
echo Lava::url('foo', ['bar' => 123]);   # http://example.com/sandbox/foo?bar=123
echo Lava::url('/foo', 'bar=123', TRUE); # http://example.com/foo?zzz=456&bar=123
```


## Маршруты


### Lava::route([rule [, cond]]) : route

Плейсхолдер `:name` соответствует полному фрагменту `([^\/]+)`

Плейсхолдер `#name` соответствует имени`([^\/]+?)(?:\.\w*)?`

Плейсхолдер `*name` соответствует оставшейся части `(.+)`

В дополнительных условиях `cond` можно добавить ограничение по переменным окружения

Если правило начинается не со слеша, то оно будет дополнено публичной папкой `Lava::pub()`

```php
// URL: http://example.com/foo1.bar/foo2.bar/foo3.bar/foo4.bar
Lava  ::route('/:node1/#node2/*node3')
      ->to   (function($node1, $node2, $node3) { // обработчик
          echo $node1;                           #  foo1.bar
          echo $node2;                           #  foo2
          echo $node3;                           #  foo3.bar/foo4.bar
      });
// поиск маршрута
Lava::routes_match();

// ограничение по окружению
Lava::route('/foo', [
    'method'     => ['GET', 'HEAD'], // если метод GET или HEAD
    'user_addr'  => '127.0.0.1',     // и пользователь локальный
    'user_agent' => '/^Mozilla/',    // и браузер Mozilla
]);

// ограничение только по методу
Lava::route('/foo', 'DELETE');
```

### Lava::route_get([rule]) : route

Ограничить маршрут методом GET

```php
Lava::route_get ('/foo');
// аналог
Lava::route     ('/foo', 'GET');
```

### Lava::route_post([rule]) : route

```php
Lava::route_post('/foo');
```

### Lava::routes_match() : completed

Выполняет обработчики совпавших маршрутов

Если обработчик ничего не возвращает, то прекращается поиск маршрутов

Если обработчик возвращает `FALSE`, то прекращается поиск маршрутов, обработчик не считается как выполненный

Если обработчик возвращает `TRUE`, то продолжается проверка остальных в цепочке маршрутов

Возвращает количество выполненных обработчиков

```php
Lava::routes_match();
```

### route->cond(cond) : route

Добавить к маршруту ограничение по окружению

```php
Lava::route('/foo')
    ->cond (['user_addr' => '/^192\.168\./']);
```

### route->name(name) : route

Служит для преобразования маршрута в путь

```php
// URL: http://example.com/foo/123
Lava::route('/foo/#id')
    ->name ('route_name')
    ->to   (function($id) {
        echo Lava::uri('route_name', ['id' => $id + 1]); #  /foo/124
    });
```

### route->to(mixed) : route

Обработчик маршрута

Метод по умолчанию `index`


```php
// функция
Lava::route('/foo')->to(function() {echo 'hello';});

// класс|неймспейс, метод
Lava::route('/foo')->to('Controller\Foo', 'bar');

// файл, метод
Lava::route('/foo')->to('controller/Foo.php', 'bar');
// имя класса должно совпадать с именем файла
// будет создан экземпляр класса Foo и вызван метод bar

// файл, класс|неймспейс, метод
Lava::route('/foo')->to('controller/Foo.php', 'Ctrl\Foo', 'bar');
// если класс отличается от имени файла или нужно указать неймспейс
```


## Рендеринг


### Lava::render(handlers) : has_handler

Выполняет обработчик с типом `Lava::type()`, если не существует, то с индексом `0`

Если нет типа запрашиваемых данных, то используется `Lava::conf()->type`

Если тип `json` и есть значение `Lava::args()->callback`, возвращает `JSONP`

```php
Lava::route('/page')->to(function() {
    Lava::render([
        'html' => 'HTML CONTENT',
        'json' => ['bar' => 123],
        function () {
            echo 'OTHER TYPE: ' . Lava::type();
        },
    ]);
});

// URL: http://example.com/page.html
Lava::routes_match(); # HTML CONTENT

// URL: http://example.com/page.json
Lava::routes_match(); # {"bar":123}

// URL: http://example.com/page.xml
Lava::routes_match(); # OTHER TYPE: xml

// если Lava::conf()->type == 'html'
// URL: http://example.com/page
Lava::routes_match(); # HTML CONTENT
```

### Lava::redirect([url|uri|route [, data [, append]]]) : void

Добавляет в заголовок `Location`

```php
Lava::redirect('/foo');
```


## Безопасность


### Lava::safe()->uuid() : uuid

Возвращает UUID

Указать алгоритм хеширования можно в конфиге, по умолчанию `md5`

```php
echo Lava::safe()->uuid(); # 055fb982653fef1ae76bde78b10f7221
```

### Lava::safe()->uuid_signed() : [signed_uuid, uuid]

Возвращает подписанный UUID

Указать подпись можно в конфиге, по умолчанию пустая строка

```php
list($signed, $uuid) = Lava::safe()->uuid_signed();

echo $signed; # 31bd185d9b3929eb56ae6e4712b73962dcd6b2b55b5287117b9d65380f4146e3
echo $uuid;   # 31bd185d9b3929eb56ae6e4712b73962
```

### Lava::safe()->check(signed_uuid) : uuid

Проверяет подписанный UUID

```php
echo Lava::safe()->check($signed); # 31bd185d9b3929eb56ae6e4712b73962
```

### Lava::safe()->salt(size) : random_string

Возвращает случайную строку заданной длины

Изменить список доступных символов можно в конфиге, по умолчанию `0123456789abcdef`

```php
echo Lava::safe()->salt(16); # f8da4f571ec3de9d
```


## Валидация

### Lava::is_valid(val, tests) : bool_result

Тесты:

- tinyint[:unsigned]
- smallint[:unsigned]
- mediumint[:unsigned]
- integer[:unsigned]
- bigint[:unsigned]
- numeric[:precision[:scale]]
- boolean
- string[:min_size[:max_size]]
- char[:size]
- email
- url
- ipv4
- date
- time
- datetime
- lt[:num]
- lte[:num]
- gt[:num]
- gte[:num]

- bool
- array
- regexp
- function

```php
// строка от 1 до 20 символов и соответствует Email
echo Lava::is_valid('me@example.com', ['string:1:20', 'email']); # TRUE
```


## Хранилище\PDO

### Lava\Storage::source('PDO', opts) : storage

Создание

```php
$storage = Lava\Storage::source('PDO', [
    'dsn'      => 'mysql:unix_socket=...mysqld.sock;dbname=name',
    'username' => 'root',
    'password' => '',
]);
```

### storage->exec(query[, bind]) : row_count

Запускает SQL-запрос на выполнение и возвращает количество строк, задействованых в ходе его выполнения

```php
$storage->exec('DELETE FROM users');

$storage->exec(
    'INSERT INTO users (login, email) VALUES (?, ?)',
    ['username', 'abc@mail']
);

$storage->exec(
    'INSERT INTO users (login, email) VALUES (:login, :email)',
    ['login' => 'username', 'email' => 'abc@mail']
);
```

### storage->fetch(query[, bind]) : row

Извлечение строки из результирующего набора

```php
$user = $storage->fetch('SELECT * FROM users WHERE id = ?', 123);
```

### storage->fetch_all(query[, bind[, index]]) : rows

Извлечение всех строк из результирующего набора

`index` используется для указания названия поля, значение которого станет индексом

```php
$users = $storage->fetch_all('SELECT * FROM users');
```

### storage->last_insert_id() : id

ID последней вставленной строки

```php
$id = $storage->last_insert_id();
```

### storage->error() : error_info

Сообщение об ошибке, заданное драйвером

```php
$error = $storage->error();
```

### storage->factory([target]) : factory

Фабрика запросов

#### factory->get([index]) : rows

Выборка данных

```php
// проиндексировать данные значением id
$data = $storage->factory('users')->get('id');
# query: SELECT * FROM `users`
```

#### factory->one() : row

Выборка одной записи

```php
$data = $storage->factory('users')->one();
# query: SELECT * FROM `users` LIMIT ?
# bind:  1
```

#### factory->columns(expression) : factory

Столбцы или вычисления

```php
$data = $storage
    ->factory('users')
    // columns(expression)
    ->columns('id')
    // columns([alias => expression, ...])
    ->columns(['full_name' => 'CONCAT(first_name, " ", last_name)'])
    ->get();
# query: SELECT `id`, CONCAT(first_name, " ", last_name) AS `full_name` FROM `users`
```

#### factory->*join(target, relations[, bind]) : factory

Объединение таблиц

```php
$data = $storage
    ->factory('users')
    ->join('profiles', 'id')
    ->left_join('sessions', 'sessions.user_id = users.id')
    ->right_join('roles', ['roles.user_id' => 'users.id', 'roles.id' => '?'], 123)
    ->get();
# query: SELECT * FROM `users`
#        JOIN `profiles` USING (`id`)
#        LEFT JOIN `sessions` ON sessions.user_id = users.id
#        RIGHT JOIN `roles` ON `roles`.`user_id` = `users`.`id` AND `roles`.`id` = ?
# bind:  123
```

#### factory->filter*(expression) : factory

Фильтрация данных: `filter_eq`, `filter_ne`, `filter_lt`, `filter_gt`, `filter_lte`, `filter_gte`, `filter_like`, `filter_not_like`, `filter_in`, `filter_not_in`, `filter_between`, `filter_is_null`, `filter_is_not_null`, `filter_raw`

Изменение контекста: `filter_and`, `filter_or`, `filter_not`

`filter` работает в зависимости от контекста, для данных как `filter_eq`, для замыканий как `filter_and`

```php
$data = $storage
    ->factory('users')
    // filter(key, val)
    ->filter_eq('id', 123)
    // filter(expression)
    ->filter_ne(['name' => 'guest', 'email' => 'test'])
    ->get();
# query: SELECT * FROM `users` WHERE (`id` = ? AND (`name` != ? AND `email` != ?))
# bind:  123, 'guest', 'test'

$data = $storage
    ->factory('users')
    ->filter_or(['name' => 'guest', 'email' => 'test'])
    ->get();
# query: SELECT * FROM `users` WHERE (`name` = ? OR `email` = ?)
# bind:  'guest', 'test'

$data = $storage
    ->factory('users')
    ->filter_or(function($filter) {
        // в замыкании без префикса filter_
        $filter ->like('name', '%test%')
                ->and(function($filter) {
                    $filter->gt('id', 10)->lte('id', 20);
                })
                ->is_not_null('email');
    })
    ->get();
# query: SELECT * FROM `users` WHERE (`name` LIKE ? OR (`id` > ? AND `id` <= ?) OR `email` IS NOT ?
# bind:  '%test%', 10, 20, NULL

$data = $storage
    ->factory('users')
    ->filter_not(function($filter) {
        $filter ->or(['name' => 'guest', 'email' => 'test'])
                ->in('role_id', [2, 4, 6]);
    })
    ->get();
# query: SELECT * FROM `users` WHERE NOT ((`name` = ? OR `email` = ?) AND `role_id` IN (?, ?, ?))
# bind:  'guest', 'test', 2, 4, 6

// подзапросы

$data = $storage
    ->factory('users')
    ->filter_in('id', $storage->factory('sessions')->columns('user_id')->filter('active', 1))
    ->get();
# query: SELECT * FROM `users` WHERE `id` IN (SELECT `user_id` FROM `sessions` WHERE `active` = ?)
# bind:  1
```

#### factory->group_by(expression) : factory

Группировка

```php
$data = $storage
    ->factory('users')
    ->columns(['role_id', 'count' => 'COUNT(*)'])
    ->group_by('role_id')
    ->get();
# query: SELECT `role_id`, COUNT(*) AS `count` FROM `users` GROUP BY `role_id`
```

#### factory->having*(expression) : factory

По аналогии с `filter`

Фильтрация данных: `having_eq`, `having_ne`, `having_lt`, `having_gt`, `having_lte`, `having_gte`, `having_like`, `having_not_like`, `having_in`, `having_not_in`, `having_between`, `having_is_null`, `having_is_not_null`, `having_raw`

Изменение контекста: `having_and`, `having_or`, `having_not`

`having` работает в зависимости от контекста, для данных как `having_eq`, для замыканий как `having_and`

```php
$data = $storage
    ->factory('users')
    ->columns(['role_id', 'count' => 'COUNT(*)'])
    ->group_by('role_id')
    ->having_or(function($having) {
        $having->gt('count', 2)->lte('count', 5);
    })
    ->get();
# query: SELECT `role_id`, COUNT(*) AS `count` FROM `users` GROUP BY `role_id` HAVING (`count` > ? OR `count` <= ?)
# bind:  2, 5
```

#### factory->order_by*(expression) : factory

Сортировка

```php
$data = $storage
    ->factory('users')
    ->order_by('name')
    ->order_by_desc('email')
    ->get();
# query: SELECT * FROM `users` ORDER BY `name` ASC, `email` DESC
```

#### factory->limit(count[, offset]) : factory

Ограничения количества возвращаемых записей

```php
$data = $storage
    ->factory('users')
    ->limit(10)
    ->get();
# query: SELECT * FROM `users` LIMIT ?
# bind:  10
```

#### factory->add(data[, update]) : row_count

Вставка данных

```php
$users   = $storage->factory('users');

$users->add([
    'login' => 'username',
    'email' => 'abc@mail',
]);
# query: INSERT INTO `users` SET `login` = ?, `email` = ?
# bind:  'username', 'abc@mail'

$archive = $storage->factory('archive_users');

$users->add($archive);
# query: INSERT INTO `users` SELECT * FROM `archive_users`

$users->columns(['login', 'email'])->add(
    $archive->columns(['username', 'email'])->filter_lt('id', 123)
);
# query: INSERT INTO `users` (`login`, `email`)
#        SELECT `username`, `email` FROM `archive_users` WHERE `id` < ?
# bind:  123

$storage
    ->factory('log')
    ->add(['name' => 'access', 'count' => 1], ['count = count + 1']);
# query: INSERT INTO `log` SET `name` = ?, `count` = ?
#        ON DUPLICATE KEY UPDATE count = count + 1
# bind:  'access', 1
```

#### factory->set(data) : row_count

Обновление данных

```php
$data = $storage
    ->factory('users')
    ->filter('id', 123)
    ->set(['name' => 'bar']);
# query: UPDATE `users` SET `name` = ? WHERE `id` = ?
# bind:  'bar', 123
```

#### factory->del() : row_count

Удаление данных

```php
$data = $storage
    ->factory('users')
    ->filter('id', 123)
    ->del();
# query: DELETE FROM `users` WHERE `id` = ?
# bind:  123
```

#### factory->count([key]) : count

Возвращает количество выражений

```php
$data = $storage
    ->factory('users')
    ->filter_gt('id', 123)
    ->count('email');
# query: SELECT COUNT(`email`) AS `val` FROM `users` WHERE `id` > ?
# bind:  123
```

#### factory->min(key) : value

Возвращает минимальное значение

```php
$data = $storage
    ->factory('users')
    ->filter_like('email', '%@mail%')
    ->min('id');
# query: SELECT MIN(`id`) AS `val` FROM `users` WHERE `email` LIKE ?
# bind:  %@mail%'
```

#### factory->max(key) : value

Возвращает максимальное значение

```php
$data = $storage
    ->factory('users')
    ->filter_in('role_id', [2, 3])
    ->max('id');
# query: SELECT MAX(`id`) AS `val` FROM `users` WHERE `role_id` IN (?, ?)
# bind:  2, 3
```

#### factory->avg(key) : value

Возвращает среднее значение

```php
$data = $storage
    ->factory('users')
    ->avg('id');
# query: SELECT AVG(`id`) AS `val` FROM `users`
```

#### factory->sum(key) : value

Возвращает сумму значений

```php
$data = $storage
    ->factory('users')
    ->sum('id');
# query: SELECT SUM(`id`) AS `val` FROM `users`
```
