<?php

use Lava\Stash;
use App\Dict;


class App extends Lava {

    private static
        $lang,
        $dict;


    // ---------------------------------------------------------------------- //

    public static function lang () {

        if (is_null(self::$lang)) {

            self::$lang = '';

            $langs  = self::conf()->langs();
            // приоритет: квери, куки, браузер
            $accept = array_unique(array_merge(
                self::args()  ->_get()->lang(),
                self::cookie()        ->lang(),
                array_reverse(self::env()->accept_language()),
                array_keys   ($langs)
            ));
            // ищем подходящий язык
            foreach ($accept  as  $key) {
                if (isset($langs [$key])) {
                    self::$lang = $key;
                    break;
                }
            }
        }

        return self::$lang;
    }
    public static function lang_short () {
        return preg_replace('/-.+/', '', self::lang());
    }

    public static function dict ($name = 'main', $lang = NULL) {

        $name .= '/' . ($lang ? $lang : self::lang());

        if (!isset(self::$dict[$name])) {
            self::$dict[$name] = new Dict ("dict/${name}.php");
        }

        return self::$dict[$name];
    }

    // ---------------------------------------------------------------------- //

    public static function template ($file, $data = NULL) {

        if (  strpos($file, '/') !== 0) {
            $file = "templates/${file}";
        }

        if (!($data instanceof Stash)) {
            $data = new Stash ($data);
        }

        return include $file;
    }

    // ---------------------------------------------------------------------- //

    public static function current_route_name () {
        return self::env()->route_name;
    }
}

?>
