<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Header {

    private static $types = [
        'txt'  => 'text/plain',
        'html' => 'text/html',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'gif'  => 'image/gif',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'svg'  => 'image/svg+xml',
    ];


    public function sent () {
        return headers_sent();
    }
    public function as_array () {
        return headers_list();
    }

    public function status ($code) {
        return http_response_code($code);
    }

    public function type ($type, $charset = NULL) {

        if (   isset(self::$types[$type])) {
            $type  = self::$types[$type];
        }

        if (   $charset
            && in_array($type, preg_grep(
                    '/text\/|\/javascript|\/svg\+xml/',
                    self::$types
               ))
        )
        {
            $type .= '; charset=' . $charset;
        }

        header('Content-Type: ' . $type);
    }

    public function location ($location, $status = 302) {
        header('Location: ' . $location, TRUE, $status);
    }

    public function error_401 ($auth) {
        header('WWW-Authenticate: ' . $auth, TRUE, 401);
    }

    public function disposition_inline () {
        header('Content-Disposition: inline');
    }
    public function disposition_attachment ($filename = NULL) {
        header('Content-Disposition: attachment'
            . ($filename ? "; filename=\"{$filename}\"" : '')
        );
    }

    public function no_cache () {
        header('Cache-Control: private, max-age=0');
    }
}

?>
