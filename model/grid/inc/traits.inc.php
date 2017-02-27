<?php

namespace WEPPO\Grid;

/**
 *  db:     hier wird davon ausgegangen, dass die Layoutelemente eine Repräsentation in der DB haben
 *          Unterelemente werden sich dann i.d.R. auch in der DB befinden
 *  manual: hiermit können Layoutelemente rein programmatisch, temporär erstellt werden
 */
trait HasMode {

    private $_mode;

    function db_mode() {
        $this->_mode = 'db';
    }

    function manual_mode() {
        $this->_mode = 'manual';
    }

    function is_db_mode() {
        return $this->_mode === 'db';
    }

    function is_manual_mode() {
        return $this->_mode === 'manual';
    }

}

trait HasParent {

    private $_parent;

    function set_parent(&$p) {
        $this->_parent = $p;
    }

    function &get_parent() {
        return $this->_parent;
    }

    function has_parent() {
        return property_exists($this, '_parent') && !!$this->_parent;
    }

}

trait HasChildren {

    private $_children = array();

    function add_child(&$c) {
        $c->set_parent($this);
        $this->_children[] = $c;
    }

    function &get_children() {
        return $this->_children;
    }

    function has_children() {
        return !!count($this->_children);
    }

}

trait HasOptions {

    function has_option($n) {
        if (!$this->options || !is_object($this->options))
            return false;
        if (!property_exists($this->options, $n))
            return false;
        return true;
    }

    function option($n, $d = '') {
        if (!$this->options || !is_object($this->options))
            return $d;
        if (!property_exists($this->options, $n))
            return $d;
        return $this->options->{$n};
    }

}

trait HasRenderer {

    private static $_render = array();

    static function add_renderer($k, &$obj) {
        self::$_render[$k] = $obj;
    }

    static function &get_renderer($k = null) {
        if ($k === null) {
            return self::$_render;
        } else {
            return self::$_render[$k];
        }
    }

    static function has_renderer($k) {
        return isset(self::$_render[$k]);
    }

    static function call_renderer($renderer, &$caller) {
        $re = explode('.', $renderer);
        @list($r, $p) = $re;
        $re = self::get_renderer($r);
        if ($re) {
            return $re->render($p, $caller);
        }
        return 'Renderer: ' . $renderer;
    }

}

/*
  trait IsContentProvider {
  private static $_content_provider = array();

  static function add_content_provider($k, &$obj) {
  static::$_content_provider[$k] = $obj;
  }
  static function &get_content_provider($k = null) {
  if ($k === null) {
  return self::$_content_provider;
  } else {
  return self::$_content_provider[$k];
  }
  }
  static function has_content_provider($k) {
  return isset(self::$_content_provider[$k]);
  }

  static function call_content_provider($cp, &$caller) {
  $re = explode('.', $cp);
  @list($r, $p) = $re;
  $re = static::get_content_provider($r);
  if ($re) {
  $c = $re->get_content($p, $caller);
  } else $c = null;
  if ($c===null) {
  $c = new \WEPPO\Grid\Content();
  $c->loadEmpty();
  $c->directContent = 'contentProvider: '.$cp;
  $c->name = 'contentProvider: '.$cp;
  }
  return $c;
  }
  }

  trait IsCellProvider {
  private static $_cell_provider = array();

  static function add_cell_provider($k, &$obj) {
  self::$_cell_provider[$k] = $obj;
  }
  static function &get_cell_provider($k = null) {
  if ($k === null) {
  return self::$_cell_provider;
  } else {
  return self::$_cell_provider[$k];
  }
  }
  static function has_cell_provider($k) {
  return isset(self::$_cell_provider[$k]);
  }

  static function call_cell_provider($cp, &$caller) {
  $re = explode('.', $cp);
  @list($r, $p) = $re;
  $re = self::get_cell_provider($r);
  if ($re) {
  $c = $re->get_cells($p, $caller);
  } else $c = null;
  if ($c===null) {
  $c = new \WEPPO\Grid\Cell();
  $c->loadEmpty();
  $c->directContent = 'cellProvider: '.$cp;
  $c = array($c);
  }
  return $c;
  }
  }
 */

trait CanUpCast {

    function upcastTo($class) {
        $obj = new $class();
        foreach ($this as $prop => &$v) {
            $obj->{$prop} = $v;
        }
        return $obj;
    }

}

trait IsCachable {

    abstract function getCacheIdentifier();

    abstract function getCacheFolder();

    abstract function useCache();

    abstract function invalidateParent($lang = null);

    /**
     * Cache TODO: multilevel-Cache
     * Steuerung des Cache:
     *  - $this->options->useCache
     */
    function getCacheFile($lang) {
        $filename = $lang . '.' . $this->getCacheIdentifier();
        $filename = APP_ROOT . 'data/cache/' . $this->getCacheFolder() . '/' . $filename;
        //echo $filename;
        return $filename;
    }

    function getFromCache($lang) {
        return @file_get_contents($this->getCacheFile($lang));
    }

    function writeCache($data, $lang) {
        return @file_put_contents($this->getCacheFile($lang), $data);
    }

    function invalidate($lang = null) {
        if ($lang === null) {
            # falls keine Sprache angegeben ist: alle Sprachen invalidieren
            foreach (\i18n\i18n::$availableLanguages as $k => $v) {
                $this->invalidate($k);
            }
        } else {
            # Cache-File für angegebene Sprache löschen
            $fn = $this->getCacheFile($lang);
            if (file_exists($fn)) {
                @unlink($fn);
            }
            $this->invalidateParent($lang);
        }
    }

}

