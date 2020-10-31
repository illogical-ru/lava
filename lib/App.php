<?php

use Lava\Stash;


class App extends Lava\App {

    private $lang;

    static
    private $dict;


    // ---------------------------------------------------------------------- //

    public function start () {

        $conf   = $this->conf;

        // языки
        $langs  = $conf->langs();
        // приоритет: квери, куки, браузер
        $accept = array_unique(array_merge(
            $this->args->_get()->lang(),
            $this->cookie      ->lang(),
            array_reverse($this->env->accept_language()),
            array_keys         ($langs)
        ));
        // добавляем короткие формы
        foreach (array_keys($langs) as $key) {

            $langs[$key] = $key;

            $short = preg_replace('/-.+/', '', $key);

            if (! isset($langs[$short])) {
                $langs[$short] = $key;
            }
        }
        // ищем подходящий язык
        foreach ($accept as $key) {
            if (  isset      ($langs[$key])) {
                $this->lang = $langs[$key];
                break;
            }
        }
    }

    // ---------------------------------------------------------------------- //

    public function lang ($short = FALSE) {
        return $short
            ? preg_replace('/-.+/', '', $this->lang)
            :                           $this->lang;
    }

    public function dict ($name = 'main', $lang = NULL) {

        $name .= '/' . (isset($lang) ? $lang : $this->lang());

        if (! isset(self::$dict[$name])) {
            self::$dict[$name] = new Dict ("dict/${name}.php");
        }

        return self::$dict[$name];
    }

    // --- data ------------------------------------------------------------- //

    public function storage ($name = 0, $opts = NULL) {

        if (! $opts) {

            $conf_storage = $this->conf->storage();

            if (isset  ($conf_storage[$name])) {
                $opts = $conf_storage[$name];
            }
        }

        return Lava\Storage::source($name, $opts);
    }

    // ---------------------------------------------------------------------- //

    public function template ($file, $data = NULL) {

        $app = $this;

        if (   strpos($file, '/') !== 0) {
            $file = "templates/${file}";
        }

        if (! ($data instanceof Stash)) {
            $data = new Stash ($data);
        }

        return include $file;
    }
}

?>
