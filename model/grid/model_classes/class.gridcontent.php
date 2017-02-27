<?php

namespace WEPPO\Grid;

define('CONTENT_CLASS_PAGE', null);
define('CONTENT_CLASS_BLOG', '\WEPPO\Grid\BlogPost');

class Content extends ExplicitClassTableRecord {

    /**
     * 
     */
    use HasMode;

/**
     * Parent ist eine Zelle
     */
    use HasParent;

    var $lang;

    static function getTablename() {
        return 'gridcontents';
    }

    static function categoryName($c) {
        static $a = array(
            CONTENT_CLASS_PAGE => 'Page',
            CONTENT_CLASS_BLOG => 'Blog'
        );
        if (isset($a[$c]))
            return $a[$c];
        return '?';
    }

    function getCategoryName($short = false) {
        $cn = static::categoryName($this->class);
        if ($short)
            $cn = substr($cn, 0, 1);
        return $cn;
    }

    static function createFromName($name, $type = null) {

        if ($type === null)
            static::where('class IS NULL');
        else
            static::where('class', $type);

        static::where('name', $name);
        return static::getOne();
    }

    public function __construct($id = 0, $cols = '*') {
        parent::__construct($id, $cols);
        $this->db_mode();
    }

    /**
     * String mit dem Content-HTML-Kontext versehen
     * $s wird verändert!
     */
    function wrapWithContext(&$s) {
        // TODO name richtig anpassen
        $identifier = identifier($this->name);
        $s = PHP_EOL . '<!-- Content ID ' . $this->id . ' ' . htmlentities($this->name) . ' -->' . PHP_EOL
                . '<div data-content_id="' . $this->id . '" data-content_name="' . $identifier . '" class="gridcontent gridcontent-' . $identifier . '">' . PHP_EOL
                // id="'.$identifier.'"
                . $s
                . PHP_EOL . '</div>' . PHP_EOL;
    }

    /**
     * HTML-Output erzeugen
     * in dem das Template und die Parameter geladen werden
     * und das Template mit den Parametern ausgeführt wird.
     */
    function getOutput($lang = null, $default = true) {
        if (property_exists($this, 'directContent')) {
            $result = $this->directContent;
        } else {
            $template = $this->getTemplate($lang);
            if (!$template || !$template->exists()) {
                if (empty($this->template)) {
                    $result = '';
                } else {
                    $result = __('Template Not Found.');
                }
            } else {

                $this->prepareTemplate($lang);

                $result = $template->getOutput();

                if (defined('CREATE_NOT_TRANSLATED_MESSAGE') && !$template->wasFullyTranslated()) {
                    $result = '<div class="notTranslated">' . __('Not Fully Translated') . '</div>' . $result;
                }
            }
        }
        $this->wrapWithContext($result);
        return $result;
    }

    /**
     * Template erzeugen
     * und cachen
     */
    function &getTemplate($lang, $load = true) {
        if (!property_exists($this, '_template') || $this->_template === null) {
            try {
                $t = new \WEPPO\Grid\Template($this->template, $lang, $load, $this);
                if ($this->is_manual_mode()) {
                    $t->manual_mode();
                }
                $this->_template = $t;
            } catch (\Exception $e) {
                $this->_template = null;
            }
        }
        return $this->_template;

        /*
          if (!property_exists($this, '_templates')) $this->_templates = array();

          if (!isset($this->_templates[$this->template]) || !isset($this->_templates[$this->template][$lang])) {
          try {
          $t = new \WEPPO\Grid\Template($this->template, $lang, true, $this);
          if ($this->is_manual_mode()) {
          $t->manual_mode();
          }
          $this->_templates[$this->template][$lang] = $t;
          } catch (\Exception $e) {
          $this->_templates[$this->template][$lang] = null;
          }
          }

          return $this->_templates[$this->template][$lang];
         */
    }

    function prepareTemplate($lang) {
        $template = $this->getTemplate($lang);
        $controller = \WEPPO\System::$requestHandler->CurrentController;
                    
        $template->set('baseURL', $this->getBaseURL());
        $template->set('name', identifier($this->name));
        $template->set('contentObject', $this);
    }
    
    function getBaseURL() {
        $page = \WEPPO\System::$requestHandler->CurrentPage;
        $baseURL = $page->getURL();
        if (substr($baseURL, -1) !== '/') {
            $baseURL .= '/';
        }
        return $baseURL;
    }

    function getTemplateContent() {
        $t = $this->getTemplate(null);
        if ($t) {
            return $t->getCode();
        }
        return '';
    }

    /**
     * Parameter laden
     */
    function getParams($lang) {
        \WEPPO\Grid\ContentParam::where('content_id', $this->id);
        \WEPPO\Grid\ContentParam::where('lang', $lang);
        $params = \WEPPO\Grid\ContentParam::get();
        return $params;
    }

    /**
     * Einzeldarstellung mit Kontext von Zelle, Zeile und Layout
     * Als Vorschau für Editor
     */
    function getPreviewOutput(&$context, $lang) {
        $result = $this->getOutput($lang);
        if ($context) {
            $context->wrapPreviewWithContext($result);
        }
        return $result;
    }

    function invalidate($lang = null) {
        \WEPPO\Grid\Cell::where('content_id', $this->id);
        $cells = \WEPPO\Grid\Cell::get();
        foreach ($cells as &$c) {
            $c->invalidate($lang);
        }
    }

    function is($cat) {
        return $this->class === $cat;
    }

}
