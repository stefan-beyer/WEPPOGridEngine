<?php

namespace WEPPO\Grid;

require_once WEPPO_ROOT . 'inc/View/class.template.php';

/**
 * Spezialisierung der normalen Template-Klasse:
 * 
 * Der wesentliche Teil ist die erweiterte get-Methode, die hier nun 
 * Content-Params aus der DB läd (wenn der Parameter auf dem üblichen Weg nicht gesetzt wurde).
 */
class Template extends \WEPPO\View\Template {

    use HasMode;

    //var $template;
    //var $name;
    var $short_name;
    var $lang;
    var $content;
    var $fully_translated = true;
    var $usage = null;

    function __construct($name, $lang = null, $load = true, &$content = null) {
        //echo 'GT::__construct('.$name.')<br/>';

        parent::__construct($name);

        $this->db_mode();

        $this->short_name = $name;
        $this->lang = $lang;
        $this->content = $content;

        if ($load) {
            if (!$this->exists()) {
                throw new \Exception('Template nicht gefunden.');
            }
        }

        $this->set('lang', $this->lang);
    }

    function wasFullyTranslated() {
        return $this->fully_translated;
    }

    function setName($n) {
        $this->short_name = $n;
        //echo 'GT::setName('.$n.')<br/>';
        parent::setName('contents/' . $n);
    }

    function getName() {
        return $this->short_name;
    }

    function get($n, $default = null) {

        if ($this->is_manual_mode() || parent::hasParam($n)) { # fallback mode
            return parent::get($n, $default);
        }
        // TODO: normale params auch ermöglichen


        if ($this->content->noTranslation) {
            \WEPPO\Grid\ContentParam::where('name', $n);
            \WEPPO\Grid\ContentParam::where('lang', \i18n\i18n::$defaultLanguage);
            \WEPPO\Grid\ContentParam::where('content_id', $this->content->id);
            $p = \WEPPO\Grid\ContentParam::getOne();
        } else {
            # 1. gewünschte Sprache suchen
            \WEPPO\Grid\ContentParam::where('name', $n);
            \WEPPO\Grid\ContentParam::where('lang', $this->lang);
            \WEPPO\Grid\ContentParam::where('content_id', $this->content->id);
            $p = \WEPPO\Grid\ContentParam::getOne();
            if (!$p && $default !== false) {
                # 2. Default-Sprache suchen
                \WEPPO\Grid\ContentParam::where('name', $n);
                \WEPPO\Grid\ContentParam::where('lang', \i18n\i18n::$defaultLanguage);
                \WEPPO\Grid\ContentParam::where('content_id', $this->content->id);
                $p = \WEPPO\Grid\ContentParam::getOne();
                // DefaultLang sollte als Alternative reichen
                /* if (!$p) { // TODO evtl. weg?? falls nötig wohl durch npTranslation abgedeckt
                  # 3. irgendeine Sprache suchen
                  \WEPPO\Grid\ContentParam::where('name', $n);
                  \WEPPO\Grid\ContentParam::where('content_id', $this->content->id);
                  $p = \WEPPO\Grid\ContentParam::getOne();
                  } */

                if (!$p) {
                    return '';
                } else {
                    $this->fully_translated = false;
                }
            }
        }

        // TODO Cast

        if (!$p)
            return '';

        return $p->value;
    }

    /**
     * Vorhandene Template als Liste zurückgeben
     * @param bool $load
     * @return array<\WEPPO\Grid\Template>
     */
    static function getList($load = true) {
        $d = dir(APP_ROOT . 'templ/contents/');
        $_templates = array();
        while (false !== ($entry = $d->read())) {
            if (substr($entry, 0, 1) == '.') {
                continue;
            }
            $entry = explode('.', $entry);
            unset($entry[count($entry) - 1]);
            $entry = implode('.', $entry);
            if (empty($entry)) {
                continue;
            }
            $_templates[] = $entry;
        }
        $d->close();

        sort($_templates);

        $templates = array();
        foreach ($_templates as $t) {
            $templates[] = new Template($t, null, $load);
        }

        return $templates;
    }

    function getCode() {
        if ($this->exists()) {
            return @file_get_contents($this->file);
        }
        return false;
    }

    function saveCode($data) {
        if ($this->exists()) {
            $this->invalidate();
            return @file_put_contents($this->file, $data) !== false;
        }
        return false;
    }

    function create($data = '') {
        return @file_put_contents($this->file, $data) !== false;
    }

    function changeName($name) {
        $old_name = $this->short_name;

        if (!$this->exists()) {
            return false;
        }
        if (empty($name)) {
            return false;
        }
        if ($name === $this->short_name) {
            return true;
        }
        $oldfile = $this->file;
        $this->setName($name);
        $newfile = $this->file;
        if ($this->exists()) {
            # datei für neuen name existiert schon
            # zurücksetzen
            $this->setName($this->short_name);
            return false;
        }
        if (!rename($oldfile, $newfile)) {
            # zurücksetzen
            $this->setName($this->name);
            return false;
        }

        $this->invalidate();
        $this->short_name = $name;

        # Vorkommen in Contents anpassen
        \WEPPO\Grid\Content::where('template', $old_name);
        \WEPPO\Grid\Content::update(array('template' => $name));


        return true;
    }

    function invalidate() {
        \WEPPO\Grid\Content::where('template', $this->short_name);
        $contents = \WEPPO\Grid\Content::get();
        foreach ($contents as &$c) {
            $c->invalidate();
        }
    }

    function remove() {
        if (file_exists($this->file))
            unlink($this->file);
    }

    function loadUsage() {
        \WEPPO\Grid\Content::createObjects(false);
        \WEPPO\Grid\Content::where('template', $this->short_name);
        $result = \WEPPO\Grid\Content::get(null, 'COUNT(*) AS count');
        if ($result) {
            $this->usage = $result[0]['count'];
        } else {
            $this->usage = null;
        }
    }

    function getCurrentContent() {
        return $this->content;
    }

    function getCurrentCell() {
        if (!$this->content)
            return null;
        return $this->content->get_parent();
    }

}
