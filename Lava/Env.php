<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;

use Lava\Stash;


class Env extends Stash {

    private static $aliases = [

        'server_name'     => '=localhost',
        'server_port'     => '=80',
        'server_protocol' => '=HTTP/1.0',
        'server_software' => '=PHP',

        'user'            => 'remote_user',
        'user_addr'       => 'remote_addr',
        'user_port'       => 'remote_port',
        'user_agent'      => 'http_user_agent',

        'host'            => 'http_host server_name',
        'method'          => 'request_method =GET',
        'type'            => 'content_type',
        'length'          => 'content_length =0',
        'script'          => 'script_name php_self',
        'query'           => 'query_string',
        'referer'         => 'http_referer',
    ];


    public function __construct () {

        $data = [];

        foreach ($_SERVER as $key => $val) {

            $key    = strtolower($key);

            $data[$key] = $val;

            // accept*
            if (!preg_match('/^http_(accept(?:|_\w+))$/', $key, $match)) {
                continue;
            }

            $key    = $match[1];
            $accept = [];

            foreach (preg_split('/[\s,]+/', $val) as $val) {
                preg_match(
                    '/(.*?)(?:\s*;\s*q=(\d+(?:\.\d+)?))?$/', $val, $match
                );
                $accept[$match[1]] = isset($match[2]) ? $match[2] : 1;
            }

                          arsort                  ($accept);
            $data[$key] = array_reverse(array_keys($accept));
        }

        foreach (self::$aliases as $key => $val) {

            preg_match_all('/(=)?(\S+)/', "{$key} {$val}", $match);

            foreach ($match[2] as $i => $val) {
                if (!$match[1][$i]) {
                    $val = isset($data[$val]) ? $data[$val] : NULL;
                }
                if (  isset($val)) {
                    $data[$key] = $val;
                    break;
                }
            }
        }

        $uri  = isset($data['document_uri'])
            ?         $data['document_uri']
            :         $data['script'];

        $data['uri']        = isset    ($data['request_uri'])
            ? preg_replace(
                '/\?.*/', '', urldecode($data['request_uri'])
              )
            : $uri;

        $data['is_https']   = (
               isset($data['https'])
            &&       $data['https'] != ''
            &&       $data['https'] != 'off'
        );
        $data['is_rewrite'] = $data['uri']    != $uri;
        $data['is_post']    = $data['method'] == 'POST';

        parent::__construct($data);
    }
}

?>
