Lava
========

Micro-Framework

Requirements: PHP 5.4+, 7+, 8+


## Installation


If you are using [Composer](https://getcomposer.org/), run the command

```bash
composer require illogical/lava
```

Or you can [download the archive](https://github.com/illogical-ru/lava/archive/master.zip)


## Usage


Use the Lava\App class

```php
use Lava\App;
```

If you downloaded the archive, use the autoloader

```php
require_once '.../Lava/Autoloader.php';

$al = new Lava\Autoloader;
$al->register();
```


## Environment


### Lava\App::conf([data]) : accessor

Config

```php
App::conf([
    'charset' => 'utf-8',             // encoding for HTTP headers
    'type'    => 'html',              // default type
    'home'    => '/path-to-home',     // home folder
    'pub'     => '/pub-uri',          // public folder
    'safe'    => [
        // default values
        'algo' => 'md5',              // hashing algorithm
        'sign' => '',                 // signature
        'salt' => '0123456789abcdef', // salt character set
    ],
    'storage' => [
        // default storage, named "0"
        [
            'driver'   => 'PDO',
            'dsn'      => 'mysql:.../mysqld.sock;dbname=test;',
            'username' => 'root',
            'password' =>  NULL,
        ],
        'db2' => [...],
        ...
    ],
]);

echo App::conf()->charset; # utf-8
```

### Lava\App::env() : accessor

Environment

```php
echo       App::env()->method;    # GET
var_export(App::env()->accept()); # array (0 => 'text/html', 1 => '*/*')
```

### Lava\App::args() : accessor

Variables

Value priority: custom, POST, GET

```php
// URL: http://example.com/sandbox/?foo=3&bar=4&foo=5

echo       App::args()->foo;    # 5
var_export(App::args()->foo()); # array (0 => '3', 1 => '5')
```

### Lava\App::cookie() : accessor

Cookies

Offsets for expire:

- s - second
- m - minute
- h - hour
- D - day
- W - week
- M - month
- Y - year

```php
// setting
App::cookie()->foo = 'bar';
App::cookie()->bar = [1, 2, 3];

// read
echo       App::cookie()->foo;    # bar
var_export(App::cookie()->bar()); # array (0 => '1', 1 => '2', 2 => '3')

// additional parameters: expire, path, domain, secure
App::cookie()->foo('bar', '1M');  // expire = 1 month
```


### Lava\App::host([scheme [, subdomain]]) : host

Returns the name of the host

If `scheme` is TRUE, then the current scheme

```php
echo App::host();      # host
echo App::host(TRUE);  # http://host
echo App::host('ftp'); # ftp://host
```

### Lava\App::home([node, ...]) : home

Returns the home folder

If not set in the config, then the current folder where the script is running

```php
echo App::home();             # /path-to-home
echo App::home('foo', 'bar'); # /path-to-home/foo/bar
```

### Lava\App::pub([node, ...]) : pub

Returns the public folder

If not set in the config, then the current folder

```php
echo App::pub();             # /pub-uri
echo App::pub('foo', 'bar'); # /pub-uri/foo/bar
```

### Lava\App::uri([path|route [, data [, append]]]) : uri

Returns URI

Variables from `data` will be appended as query_string

The `append` flag adds the current query_string

```php
// URL: http://example.com/sandbox/?zzz=456

echo App::uri();                        # /sandbox/
echo App::uri('foo', ['bar' => 123]);   # /sandbox/foo?bar=123
echo App::uri('/foo', 'bar=123', TRUE); # /foo?zzz=456&bar=123
```

### Lava\App::url([path|route [, data [, append]]]) : url

Returns URL

```php
// URL: http://example.com/sandbox/?zzz=456

echo App::url();                        # http://example.com/sandbox/
echo App::url('foo', ['bar' => 123]);   # http://example.com/sandbox/foo?bar=123
echo App::url('/foo', 'bar=123', TRUE); # http://example.com/foo?zzz=456&bar=123
```


## Routes


### Lava\App::route([rule [, cond]]) : route

Placeholder `:name` corresponds to the full fragment `([^\/]+)`

Placeholder `#name` matches the name `([^\/]+?)(?:\.\w*)?`

Placeholder `*name` matches the remainder `(.+)`

You can add a restriction on environment variables in the additional `cond` conditions

If the rule does not start with a slash, it will be appended to the public folder `Lava\App::pub()`

```php
// URL: http://example.com/foo1.bar/foo2.bar/foo3.bar/foo4.bar
App ::route('/:node1/#node2/*node3')
    ->to   (function($node1, $node2, $node3) { // handler
        echo $node1;                           #  foo1.bar
        echo $node2;                           #  foo2
        echo $node3;                           #  foo3.bar/foo4.bar
    });
// route search
App::routes_match();

// environment constraint
App::route('/foo', [
    'method'     => ['GET', 'HEAD'], // if the method is GET or HEAD
    'user_addr'  => '127.0.0.1',     // and the user is local
    'user_agent' => '/^Mozilla/',    // and the browser is Mozilla
]);

// method restriction only
App::route('/foo', 'DELETE');
```

### Lava\App::route_get([rule]) : route

Restrict the route to the GET method

```php
App::route_get ('/foo');
// analog
App::route     ('/foo', 'GET');
```

### Lava\App::route_post([rule]) : route

```php
App::route_post('/foo');
```

### Lava\App::routes_match() : result

Executes handlers for matched routes

If the handler returns `TRUE`, it continues checking the rest of the routes, otherwise it stops checking and returns the result of the handler

```php
App::routes_match();
```

### route->cond(cond) : route

Add an environment constraint to the route

```php
App ::route('/foo')
    ->cond (['user_addr' => '/^192\.168\./']);
```

### route->name(name) : route

Used to convert a route to a path

```php
// URL: http://example.com/foo/123
App ::route('/foo/#id')
    ->name ('route_name')
    ->to   (function($id) {
        echo App::uri('route_name', ['id' => $id + 1]); #  /foo/124
    });
```

### route->to(mixed) : route

Route handler

Default method `Lava\App::env()->method`

```php
// function
App::route('/foo')->to(function() {echo 'hello';});

// class|namespace, method
App::route('/foo')->to('Controller\Foo', 'bar');

// file, method
// the class name must match the file name
// an instance of the Foo class will be created and the bar method will be called
App::route('/foo')->to('controller/Foo.php', 'bar');

// file, class|namespace, method
App::route('/foo')->to('controller/Foo.php', 'Ctrl\Foo', 'bar');
// if the class is different from the file name or if we need to specify a namespace
```


## Rendering


### Lava\App::render(handlers) : has_handler

Executes a handler with type `Lava\App::type()`, if none exists, then with index `0`

If there is no type of the requested data, `Lava\App::conf()->type` is used

If the type is `json` and there is a `Lava\App::args()->callback` value, returns `JSONP`

```php
App::route('/page')->to(function() {
    App::render([
        'html' => 'HTML CONTENT',
        'json' => ['bar' => 123],
        function () {
            echo 'OTHER TYPE: ' . App::type();
        },
    ]);
});

// URL: http://example.com/page.html
App::routes_match(); # HTML CONTENT

// URL: http://example.com/page.json
App::routes_match(); # {"bar":123}

// URL: http://example.com/page.xml
App::routes_match(); # OTHER TYPE: xml

// if Lava\App::conf()->type == 'html'
// URL: http://example.com/page
App::routes_match(); # HTML CONTENT
```

### Lava\App::redirect([url|uri|route [, data [, append]]]) : void

Appends to the `Location` header

```php
App::redirect('/foo');
```


## Security


### Lava\App::safe()->uuid() : uuid

Returns UUID

You can specify the hashing algorithm in the config, the default is `md5`

```php
echo App::safe()->uuid(); # 055fb982653fef1ae76bde78b10f7221
```

### Lava\App::safe()->uuid_signed() : [signed_uuid, uuid]

Returns the signed UUID

You can specify the signature in the config, defaults to an empty string

```php
list($signed, $uuid) = App::safe()->uuid_signed();

echo $signed; # 31bd185d9b3929eb56ae6e4712b73962dcd6b2b55b5287117b9d65380f4146e3
echo $uuid;   # 31bd185d9b3929eb56ae6e4712b73962
```

### Lava\App::safe()->check(signed_uuid) : uuid

Checks the signed UUID

```php
echo App::safe()->check($signed); # 31bd185d9b3929eb56ae6e4712b73962
```

### Lava\App::safe()->salt(size) : random_string

Returns a random string of the given length

You can change the list of available characters in the config, default is `0123456789abcdef`

```php
echo App::safe()->salt(16); # f8da4f571ec3de9d
```


## Validation

### Lava\App::is_valid(val, tests) : bool_result

Tests:

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
// the string is between 1 and 20 characters and matches Email
echo App::is_valid('me@example.com', ['string:1:20', 'email']); # TRUE
```


## Storage\PDO

### Lava\Storage::source('PDO', opts) : storage

Creation

```php
$storage = Lava\Storage::source('PDO', [
    'dsn'      => 'mysql:unix_socket=...mysqld.sock;dbname=name',
    'username' => 'root',
    'password' => '',
]);
```

### storage->exec(query[, bind]) : row_count

Runs the SQL query and returns the number of rows involved in its execution

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

Fetching a row from the result set

```php
$user = $storage->fetch('SELECT * FROM users WHERE id = ?', 123);
```

### storage->fetch_all(query[, bind[, index]]) : rows

Fetching all rows from the result set

`index` is used to specify the name of the field whose value will become the index

```php
$users = $storage->fetch_all('SELECT * FROM users');
```

### storage->last_insert_id() : id

ID of the last inserted row

```php
$id = $storage->last_insert_id();
```

### storage->error() : error_info

Driver-defined error message

```php
$error = $storage->error();
```

### storage->factory([target]) : factory

Query factory

#### factory->get([index]) : rows

Data selection

```php
// index data with id value
$data = $storage->factory('users')->get('id');
# query: SELECT * FROM `users`
```

#### factory->one() : row

Selecting one record

```php
$data = $storage->factory('users')->one();
# query: SELECT * FROM `users` LIMIT ?
# bind:  1
```

#### factory->columns(expression) : factory

Columns or calculations

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

Table joins

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

Data filtering: `filter_eq`, `filter_ne`, `filter_lt`, `filter_gt`, `filter_lte`, `filter_gte`, `filter_like`, `filter_not_like`, `filter_in`, `filter_not_in`, `filter_between`, `filter_is_null`, `filter_is_not_null`, `filter_raw`

Context change: `filter_and`, `filter_or`, `filter_not`

`filter` works depending on context, for data as `filter_eq`, for closures as `filter_and`

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
        // in a closure without the filter_ prefix
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

// subqueries

$data = $storage
    ->factory('users')
    ->filter_in('id', $storage->factory('sessions')->columns('user_id')->filter('active', 1))
    ->get();
# query: SELECT * FROM `users` WHERE `id` IN (SELECT `user_id` FROM `sessions` WHERE `active` = ?)
# bind:  1
```

#### factory->group_by(expression) : factory

Grouping

```php
$data = $storage
    ->factory('users')
    ->columns(['role_id', 'count' => 'COUNT(*)'])
    ->group_by('role_id')
    ->get();
# query: SELECT `role_id`, COUNT(*) AS `count` FROM `users` GROUP BY `role_id`
```

#### factory->having*(expression) : factory

Similar to `filter`

Filtering data: `having_eq`, `having_ne`, `having_lt`, `having_gt`, `having_lte`, `having_gte`, `having_like`, `having_not_like`, `having_in`, `having_not_in`, `having_between`, `having_is_null`, `having_is_not_null`, `having_raw`

Context change: `having_and`, `having_or`, `having_not`

`having` works depending on context, for data as `having_eq`, for closures as `having_and`

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

Sorting

```php
$data = $storage
    ->factory('users')
    ->order_by('name')
    ->order_by_desc('email')
    ->get();
# query: SELECT * FROM `users` ORDER BY `name` ASC, `email` DESC
```

#### factory->limit(count[, offset]) : factory

Limits the number of records returned

```php
$data = $storage
    ->factory('users')
    ->limit(10)
    ->get();
# query: SELECT * FROM `users` LIMIT ?
# bind:  10
```

#### factory->add(data[, update]) : row_count

Inserting data

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

Update data

```php
$data = $storage
    ->factory('users')
    ->filter('id', 123)
    ->set(['name' => 'bar']);
# query: UPDATE `users` SET `name` = ? WHERE `id` = ?
# bind:  'bar', 123
```

#### factory->del() : row_count

Deleting data

```php
$data = $storage
    ->factory('users')
    ->filter('id', 123)
    ->del();
# query: DELETE FROM `users` WHERE `id` = ?
# bind:  123
```

#### factory->count([key]) : count

Returns the number of expressions

```php
$data = $storage
    ->factory('users')
    ->filter_gt('id', 123)
    ->count('email');
# query: SELECT COUNT(`email`) AS `val` FROM `users` WHERE `id` > ?
# bind:  123
```

#### factory->min(key) : value

Returns the minimum value

```php
$data = $storage
    ->factory('users')
    ->filter_like('email', '%@mail%')
    ->min('id');
# query: SELECT MIN(`id`) AS `val` FROM `users` WHERE `email` LIKE ?
# bind:  '%@mail%'
```

#### factory->max(key) : value

Returns the maximum value

```php
$data = $storage
    ->factory('users')
    ->filter_in('role_id', [2, 3])
    ->max('id');
# query: SELECT MAX(`id`) AS `val` FROM `users` WHERE `role_id` IN (?, ?)
# bind:  2, 3
```

#### factory->avg(key) : value

Returns the average value

```php
$data = $storage
    ->factory('users')
    ->avg('id');
# query: SELECT AVG(`id`) AS `val` FROM `users`
```

#### factory->sum(key) : value

Returns the sum of values

```php
$data = $storage
    ->factory('users')
    ->sum('id');
# query: SELECT SUM(`id`) AS `val` FROM `users`
```


## Model\SQL


```php
use Lava\Model\SQL;

class User extends SQL {

    // meta data
    protected static

        // optionally, storage name
        // name from Lava\App::conf()->storage()
        // default is "0"
        $storage = '0',

        // optionally, table name
        // defaults to a class name in snake case with an "s" at the end
        $table   = 'users',

        // optionally, the name of the primary key
        // defaults to "id"
        $id      = 'id',

        // columns description
        $columns = [
            'key' => [
                // value cannot be NULL
                'not_null' => FALSE|TRUE,
                // value is unique
                'unique'   => FALSE|TRUE,
                // default value
                'default'  => VALUE,
                // validation tests
                'valid'    => [tests],
            ],
            ...
        ],

        // optionally, default limit
        // if not specified at selection
        // unrestricted by default
        $limit   = 0;
}
```


### Creation


```php
$user = new User;

$user->name  = 'foo';
$user->email = 'mail@example.com';

$user->save();
```


### Selecting


```php
// selecting one object by ID
$user  = User::one(123);

echo $user->name;

// selecting a collection by condition
$users = User::find(['active' => TRUE])->get();

foreach ($users as $user) {
    echo $user->name;
}
```


### Default values


```php
class Session extends SQL {

    // method name must start with the prefix "column_default_"
    public static function column_default_created () {
        return date('Y-m-d H:i:s');
    }
}

$session = new Session;

echo $session->created; // now
```


### Relationship


```php
class User extends SQL {

    public function sessions () {
        return $this
            ->has_many(Session::classname());
    }

    // additional filters
    public function active_sessions () {
        return $this
            ->sessions()
            ->filter(['active' => TRUE]);
    }
}

class Session extends SQL {

    public function user () {
        return $this->belongs_to(User::classname());
    }
}

$user   = User::one(123);

// property returns a collection
foreach ($user->sessions as $session) {
    echo $session->id;
}

// method returns resultset
$active = $user ->sessions()
                ->filter(['active' => TRUE])
                ->get();

// similarly
$active = $user ->active_sessions;
```


### Import & Export


```php
class Session extends SQL {

    public static function import ($data) {

        if (isset($data['ip'])) {
            $data['ip'] = long2ip($data['ip']);
        }

        return $data;
    }

    public static function export ($data) {

        if (isset($data['ip'])) {
            $data['ip'] = ip2long($data['ip']);
        }

        return $data;
    }
}
```
