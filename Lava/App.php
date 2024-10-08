<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;

use Lava\Stash;
use Lava\Header;
use Lava\Env;
use Lava\Cookie;
use Lava\Session;
use Lava\Args;
use Lava\Route;
use Lava\Storage;
use Lava\Safe;
use Lava\Validator;


class App {

    private static
        $conf,
        $header,
        $env,
        $cookie,
        $session,
        $args,
        $routes = [],
        $safe;


    public static function conf ($data = NULL) {

        if (!self::$conf) {
            self::$conf = new Stash;
        }

        if ( is_array($data)) {
            foreach  ($data as $key => $val) {
                self::$conf -> $key =  $val;
            }
        }

        return self::$conf;
    }

    public static function header () {

        if (!self::$header) {
            self::$header = new Header;
        }

        return self::$header;
    }
    public static function header_type ($type) {
        self::header()->type($type, self::conf()->charset);
    }
    public static function header_401 ($auth = 'Bearer') {
        self::header()->error_401($auth);
    }
    public static function header_403 () {
        self::header()->status(403);
    }
    public static function header_404 () {
        self::header()->status(404);
    }

    public static function env () {

        if (!self::$env) {
            self::$env = new Env;
        }

        return self::$env;
    }

    public static function cookie () {

        if (!self::$cookie) {
            self::$cookie = new Cookie;
        }

        return self::$cookie;
    }

    public static function session () {

        if (!self::$session) {
            self::$session = new Session;
        }

        return self::$session;
    }

    public static function args () {

        if (!self::$args) {
            self::$args = new Args;
        }

        return self::$args;
    }

    public static function type () {

        if     (!self::env()->is_rewrite) {
            $type = self::args()->type;
        }
        elseif ( preg_match('/\.(\w+)$/', self::env()->uri, $match)) {
            $type = end($match);
        }

        if     (!isset($type)) {
            $type = self::conf()->type;
        }

        return strtolower($type);
    }

    public static function host ($scheme = NULL, $subdomain = NULL) {

        $host = self::conf()->host
            ?   self::conf()->host
            :   self::env ()->host;

        if ($scheme === TRUE) {
            $scheme = self::env()->is_https ? 'https' : 'http';
        }
        if ($subdomain) {
            $host   = join('.', array_merge((array)$subdomain, [$host]));
        }
        if ($scheme) {
            $host   = "{$scheme}://{$host}";
        }

        return $host;
    }

    public static function home ($path = NULL) {

        $home = self::conf()->home
            ?   self::conf()->home
            :   getcwd();

        return join('/', array_merge([$home], (array)$path));
    }

    public static function pub ($path = NULL) {

        if     (self::conf()->pub) {
            $pub = self::conf()->pub;
        }
        elseif (self::env()->is_rewrite) {
            $pub = preg_replace('|/[^/]*$|', '', self::env()->script);
        }
        else   {
            $pub = NULL;
        }

        return preg_replace(
            '|/+|', '/', join('/', array_merge([$pub], (array)$path))
        );
    }

    public static function uri ($uri = NULL, $data = NULL, $append = FALSE) {

        if     (is_null($uri)) {
            $uri   = self::env()->uri;
        }
        elseif (preg_match('|^(?:[a-z]+:)?//[^/]+(.*)|i', $uri, $match)) {
            $uri   = $match[1] ? $match[1] : '/';
        }
        else   {

            $route = self::route_by_name($uri);

            if     ($route) {
                $uri  = $route->uri($data);
                $data = NULL;
            }
            elseif (strpos($uri, '/') !== 0) {
                $uri  = rtrim(self::env()->uri, '/') . '/' . $uri;
            }
        }

        if   ($data || $append) {

            $data  = self::args()->_query($data, $append);

            if ($data) {
                $uri .= (strpos($uri, '?') ? '&' : '?') . $data;
            }
        }

        return $uri;
    }

    public static function url () {

        $args = func_get_args();

        if   ( isset($args[0])
            && preg_match('|^([a-z]+:)?(//[^/]+)|i', $args[0], $match)
        )
        {
            $host = $match[1] ? $match[0] : 'http:' . $match[2];
        }
        else {
            $host = self::host(TRUE, key_exists(3, $args) ? $args[3] : NULL);
        }

        return $host . call_user_func_array([__CLASS__, 'uri'], $args);
    }

    public static function redirect () {
        self::header()->location(call_user_func_array(
            [__CLASS__, 'url'], func_get_args()
        ));
    }

    public static function route ($rule = '', $cond = NULL) {

        if (strpos($rule, '/') !== 0) {
            $rule = self::pub($rule);
        }

        return self::$routes[] = new Route ($rule, $cond);
    }
    public static function route_get  ($rule = '') {
        return self::route($rule, 'GET');
    }
    public static function route_post ($rule = '') {
        return self::route($rule, 'POST');
    }
    public static function route_by_name ($name) {
        if ($name) {
            foreach (self::$routes as $i => $route) {
                if ($route->name() == $name) {
                    return $route;
                }
            }
        }
    }
    public static function routes_allow_methods ($skip = NULL) {

        $uri     = self::env()-> uri;
        $env     = self::env()->_data();
        $methods = [];
        $has     = FALSE;
        $skip    = array_merge((array)$skip, ['method']);

        foreach (self::$routes as $route) {
            if ($route->test($uri, $env, $skip) !== NULL) {

                foreach ($route->allow_methods() as $name) {
                    $methods[$name] = TRUE;
                }

                $has = TRUE;
            }
        }

        return $has && !$methods ? ['*'] : array_keys($methods);
    }
    public static function routes_match () {

        $uri    = self::env()-> uri;
        $env    = self::env()->_data();
        $result = FALSE;

        foreach (self::$routes as $route) {

            $to   = $route->to  ();
            $args = $route->test($uri, $env);

            if   (!$to || is_null($args)) {
                continue;
            }

            if   ( count($to) == 1 && is_callable($to[0])) {
                $to      = array_shift($to);
            }
            else {

                $target  = array_shift($to);
                $is_file = preg_match ('/[^\\\\\w]/', $target);
                $method  = $to
                    ? array_pop ($to)
                    : strtolower($env['method']);

                if     ($is_file) {
                    require_once $target;
                }

                if     ($to) {
                    $class = join('\\', $to);
                }
                elseif ($is_file) {
                    $info  = pathinfo($target);
                    $class = $info['filename'];
                }
                else   {
                    $class = $target;
                }

                $to      = [new $class, $method];
            }

            self::env()->route_name = $route->name();

            $result = call_user_func_array($to, $args);

            if   ( $result !== TRUE) {
                break;
            }
        }

        return $result;
    }

    public static function render ($handler) {

        $type     = self::type();
        $callback = self::args()->callback;

        if     (isset($handler[$type])) {
            $data =   $handler[$type];
        }
        elseif (isset($handler[    0])) {
            $data =   $handler[    0];
        }
        else   {
            return FALSE;
        }

        self::header_type(
            $type == 'json' && $callback ? 'js' : $type
        );
        self::header()->no_cache();

        if     (    is_callable   ($data)) {
            $data = call_user_func($data);
        }

        if     (isset($data)) {

            if ($type == 'json') {

                $data = json_encode($data);

                if ($callback) {
                    $data = "{$callback}({$data});";
                }
            }

            echo $data;
        }
    }

    public static function safe ($opts = NULL) {

        $conf = self::conf()->safe();

        if (!self::$safe) {
            self::$safe = new Safe ($conf);
        }

        if (!$opts) {
            return self::$safe;
        }

        if ( is_string($opts)) {
            $opts = ['algo' => $opts];
        }

        return new Safe (
            array_merge($conf, (array)$opts)
        );
    }

    public static function is_valid ($val, $tests) {
        return (new Validator ($tests))->test($val);
    }

    public static function storage ($name = 0, $opts = NULL) {

        if (!$opts) {

            $conf = self::conf()->storage();

            if (isset  ($conf[$name])) {
                $opts = $conf[$name];
            }
        }

        return Storage::source($name, $opts);
    }
}

?>
