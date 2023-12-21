<?php

/*!
 * https://github.com/illogical-ru/lava
 *
 * Copyright illogical
 * Released under the MIT license
 */

namespace Lava;


class Validator {

    private $queue = [];


    public function __construct ($tests = NULL) {
        if ($tests) {
            $this->add($tests);
        }
    }


    public function __call ($key, $args) {

        array_unshift($args, $key);

        return $this->add(join(':', $args));
    }


    public function add ($tests) {

        foreach ((array)$tests as $test) {
            if     ( is_bool   ($test)) {
                $this->queue[] = function($val) use  ($test) {
                    return $val == $test;
                };
            }
            elseif ( is_array  ($test)) {
                $this->queue[] = function($val) use  ($test) {
                    return in_array($val, $test);
                };
            }
            elseif ( is_object ($test) && is_callable($test)) {
                $this->queue[] = $test;
            }
            elseif (!is_string ($test)) {
                continue;
            }
            elseif ( preg_match('/^\/.*\/[imsuxADEJSUX]*$/', $test)) {
                $this->queue[] = function($val) use ($test) {
                    return preg_match($test, $val);
                };
            }
            else   {

                $opts =         explode(':', $test);
                $name = 'is_' . array_shift ($opts);

                if (!method_exists($this, $name)) {
                    throw new \Exception("Unknown test: {$name}");
                }

                $self = [$this, $name];

                $this->queue[] = function($val) use ($self, $opts) {
                    array_unshift($opts, $val);
                    return call_user_func_array     ($self, $opts);
                };
            }
        }

        return $this;
    }

    public function test () {

        $args = func_get_args();

        foreach ($this->queue as $test) {
            if (!call_user_func_array($test, $args)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    // --- tests ------------------------------------------------------------ //

    public static function is_int ($val, $size = 4, $unsigned = FALSE) {

        $size = pow(256, $size);

        return is_numeric($val)
            && $val >= ($unsigned ? 0     : -$size / 2)
            && $val <= ($unsigned ? $size :  $size / 2) - 1;
    }
    public static function is_tinyint   ($val, $unsigned = FALSE) {
        return self::is_int($val, 1, $unsigned);
    }
    public static function is_smallint  ($val, $unsigned = FALSE) {
        return self::is_int($val, 2, $unsigned);
    }
    public static function is_mediumint ($val, $unsigned = FALSE) {
        return self::is_int($val, 3, $unsigned);
    }
    public static function is_integer   ($val, $unsigned = FALSE) {
        return self::is_int($val, 4, $unsigned);
    }
    public static function is_bigint    ($val, $unsigned = FALSE) {
        return self::is_int($val, 8, $unsigned);
    }

    public static function is_numeric   ($val, $prec = 0, $scale = 0) {

        if (! is_numeric($val)) {
            return;
        }
        if (!($prec || $scale)) {
            return TRUE;
        }

        return $prec  && $prec <= 1000
            && $scale <= $prec
            && pow(10, $prec - $scale) > abs($val);
    }

    public static function is_boolean ($val) {
        return is_bool($val);
    }

    public static function is_string ($val, $min, $max = NULL) {

        if (!(is_numeric($val) || is_string($val))) {
            return;
        }

        $len = is_callable('mb_strlen')
            ?  mb_strlen  ($val)
            :     strlen  ($val);

        return $len >= $min && (!$max || $len <= $max);
    }
    public static function is_char ($val, $size = 1) {
        return self::is_string($val, $size, $size);
    }

    public static function is_email ($val) {
        return filter_var($val, FILTER_VALIDATE_EMAIL);
    }

    public static function is_url ($val) {
        return filter_var($val, FILTER_VALIDATE_URL);
    }

    public static function is_ipv4 ($val) {
        return filter_var($val, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    public static function is_date ($val) {
        return is_string ($val)
            && preg_match('/^(\d+)-(\d+)-(\d+)$/', $val, $match)
            && checkdate ($match[2], $match[3], $match[1]);
    }
    public static function is_time ($val) {
        return is_string ($val)
            && preg_match(
                    '/^(?:[01]?\d|2[0-3]):[0-5]?\d(?::[0-5]?\d)?$/',
                    $val
               );
    }
    public static function is_datetime ($val) {
        return is_string ($val)
            && preg_match('/^(\S+)\s(\S+)$/', $val, $match)
            && self::is_date($match[1])
            && self::is_time($match[2]);
    }

    public static function is_lt  ($val, $num = 0) {
        return is_numeric($val) && $val <  $num;
    }
    public static function is_lte ($val, $num = 0) {
        return is_numeric($val) && $val <= $num;
    }
    public static function is_gt  ($val, $num = 0) {
        return is_numeric($val) && $val >  $num;
    }
    public static function is_gte ($val, $num = 0) {
        return is_numeric($val) && $val >= $num;
    }
}

?>
