<?php

use Lava\Stash;
use Lava\Env;
use Lava\Cookie;
use Lava\Session;
use Lava\Args;
use Lava\Route;
use Lava\Storage;
use Lava\Safe;
use Lava\Validator;


class Lava {

    private static
        $conf,
        $env,
        $cookie,
        $session,
        $args,
        $types  = [
                       'application/octet-stream',
            'txt'   => 'text/plain',
            'html'  => 'text/html',
            'js'    => 'text/javascript',
            'json'  => 'application/json',
            'jsonp' => 'application/javascript',
        ],
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

        $host = self::conf()->host && $subdomain !== TRUE
            ?   self::conf()->host
            :   self::env ()->host;

        if ($scheme === TRUE) {
            $scheme = self::env()->is_https ? 'https' : 'http';
        }
        if ($subdomain && $subdomain !== TRUE) {
            $host   = join('.', array_merge((array)$subdomain, [$host]));
        }
        if ($scheme) {
            $host   = "${scheme}://${host}";
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

        $pub  = self::conf()->pub
            ?   self::conf()->pub
            :   preg_replace('|/[^/]*$|', '', self::env()->script);

        return join('/', array_merge([$pub], (array)$path));
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
            $host = App::host(TRUE, key_exists(3, $args) ? $args[3] : TRUE);
        }

        return $host . call_user_func_array([__CLASS__, 'uri'], $args);
    }

    public static function url_ref_or () {

        $env = self::env();

        if   (  preg_match(
                    '|^[a-z]+://([^/]+)([^?]*)|i', $env->referer, $match
                )
            && (strcasecmp($env->host, $match[1]) || $env->uri != $match[2])
        )
        {
            return $env->referer;
        }
        else {
            return call_user_func_array([__CLASS__, 'url'], func_get_args());
        }
    }

    public static function redirect () {

        $location = call_user_func_array([__CLASS__, 'url'], func_get_args());

        header('Location: ' . $location, TRUE, 302);
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
    public static function routes_allow_methods () {

        $uri     = self::env()-> uri;
        $env     = self::env()->_data();
        $methods = [];
        $has     = FALSE;

        foreach (self::$routes as $route) {
            if ($route->test($uri, $env, 'method') !== NULL) {

                foreach ($route->allow_methods() as $name) {
                    $methods[$name] = TRUE;
                }

                $has = TRUE;
            }
        }

        return $has && !$methods ? ['*'] : array_keys($methods);
    }
    public static function routes_match () {

        $uri  = self::env()-> uri;
        $env  = self::env()->_data();
        $done = 0;

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
                $method  = $to ? array_pop($to) : 'index';

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

            $result = call_user_func_array($to, $args);

            if   ( $result !== FALSE) {
                $done++;
            }
            if   ( $result !== TRUE) {
                break;
            }
        }

        return $done;
    }

    public static function render ($handler) {

        $type     = self::type();
        $callback = self::args()->callback;

        if     ( isset($handler[$type])) {
            $case =    $handler[$type];
        }
        elseif ( isset($handler[    0])) {
            $case =    $handler[    0];
        }
        else   {
            return;
        }

        if     ( $type == 'json' && $callback) {
            $type = 'jsonp';
        }

        if     (!headers_sent()) {

            if   (isset(self::$types[$type])) {

                $content_type = self::$types[$type];

                if (self::conf()->charset) {
                    $content_type .= '; charset=' . self::conf()->charset;
                }
            }
            else {
                $content_type = self::$types[0];
            }

            header("Content-Type: ${content_type}");
            header('Expires: 0');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }

        $data     = is_object($case) && is_callable($case)
            ? call_user_func ($case)
            :                 $case;

        if     ( isset($data)) {

            if (preg_match('/^jsonp?$/', $type)) {
                $data = json_encode($data);
            }
            if ($type == 'jsonp') {
                $data = "${callback}(${data});";
            }

            echo $data;
        }

        return TRUE;
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
