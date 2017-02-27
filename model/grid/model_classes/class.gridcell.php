<?php

namespace WEPPO\Grid;

/**
 * Einer Zelle kann Inhalt zugeordnet werden.
 * Je nach Layout-Typ sind Zellen nebeneinander oder untereinander.
 */
class Cell extends ExplicitClassTableRecord {

    use HasMode;

    /**
     * Parent ist eine Reihe
     */
    use HasParent;

    /**
     * 
     */
    use HasOptions;

    /**
     * Upcast zu spezialisiertem Objekt
     */
    //use CanUpCast;

    /**
     * Multilevel Cache
     */
    use IsCachable;

    function getCacheIdentifier() {
        return 'CELL-ID_' . $this->id;
    }

    function getCacheFolder() {
        static $cacheFolder = 'grid/cell';
        return $cacheFolder;
    }

    function useCache() {
        //return $this->option('useCache', false);
        return $this->useCache;
    }

    function invalidateParent($lang = null) {
        $r = $this->getRowContext();
        if ($r)
            $r->invalidate($lang);
    }

    # Für DB-Anbindung

    static function getTablename() {
        return 'gridcells';
    }

    # Für DB-Anbindung

    static function getCast() {
        return array_merge(parent::getCast(), array(
            'options' => 'json',
        ));
    }

    
    
    public function __construct($id = 0, $cols = '*') {
        parent::__construct($id, $cols);
        $this->db_mode();
    }

    /**
     * Content-Objekt laden (nicht String!)
     */
    function getContent() {
        # im manual-mode wird das Content-Objekt in $this->_content erwartet
        if ($this->is_manual_mode())
            return $this->_content;

        # Content-Objekt wird anhand von content_id aus DB geholt
        if (!$this->content_id)
            return null;

        try {
            $c = new \WEPPO\Grid\Content($this->content_id);
            $c->set_parent($this);
            return $c;
        } catch (\Exception $e) {
            
        }

        return null;
    }

    /**
     * String mit dem Cell-HTML-Kontext versehen
     * Seiteneffekt auf $s !
     */
    function wrapWithContext(&$s) {

        # wir brauchen den Layout-Context um den Typ des Layouts zu bestimmen und damit
        # das genaue Verhalten einer Zelle
        $p = $this->getLayoutContext();
        if (!$p)
            throw new \Exception('Cell::wrapWithContext: no LayoutContext');


        if ($p->isTypeRowsfirst()) {

            # Zelle ist eine Spalte mit entsprechender Breite ($this->size)
            $s = PHP_EOL . '<div data-cell_id="' . $this->id . '" class="' . getSizeClass($this->size) . ' columns gridcell ' . $this->classes . '" style="' . $this->styles . '">' . PHP_EOL
                    . $s
                    . PHP_EOL . '</div>' . PHP_EOL;
        } else if ($p->isTypeColsfirst()) {

            # Zelle ist eine Zeile mit voller Breite
            $s = PHP_EOL . '<div data-cell_id="' . $this->id . '" class="gridcell ' . $this->classes . '" style="' . $this->styles . '">' . PHP_EOL
                    . $s
                    . PHP_EOL . '</div>' . PHP_EOL;
        }
    }

    /**
     * HTML-Output Erzeugen,
     * in dem das Content-Objekt geladen und ausgeführt wird.
     */
    function getOutput($lang = null, $default = true) {
        $fromCache = false;

        if (property_exists($this, 'directContent')) {
            # Wenn ein directContent gesetzt ist, wird nur dieser verwendet.
            # an dieser Stelle ist es dann nicht mehr Sprachabhängig.
            $result = $this->directContent;

            $this->wrapWithContext($result);
        } else {

            $useCache = $this->useCache();
            $fromCache = false;
            # aus cache laden
            if ($useCache) {
                $ret = $this->getFromCache($lang);
                if (is_string($ret)) {
                    $result = $ret;
                    $fromCache = true;
                }
            }



            if (!$fromCache) {
                # Content-Objekt holen
                $content = $this->getContent();

                # Wenn die Zelle sagt: template wechseln:
                # temporär anderes Template verwenden: nur name ändern!
                $changeTemplate = $this->option('changeTemplate');
                if ($content && !empty($changeTemplate)) {
                    $t = $content->getTemplate($lang);
                    if ($t) {
                        $t->setName($changeTemplate);
                    }
                }

                if ($content) {
                    $result = $content->getOutput($lang, $default);
                } else {
                    # Wenn kein Content-Object vorhanden ist,
                    # hat der Provider evtl. keines geliefert... Meldung geben!
                    if ($class = $this->class) {
                        $result = '<small>Provider <code>' . $class . '</code> did not provide content in this context.</small>';
                    } else {
                        $result = 'no content';
                    }
                }

                $this->wrapWithContext($result);

                # cachen
                if ($useCache) {
                    if ($this->writeCache($result, $lang)) {
                        
                    }
                }
            }
        }


        # zur besseren übersicht im HTML-Code: HTML-Kommentar
        $result = "\n<!--       Cell " . ($fromCache ? ' AUS CACHE ' : ' ON THE FLY ') . " ID " . $this->id . "\n–––––––––––––––––––––––––––––––––––––––––––––––– -->\n" . $result;
        return $result;
    }

    function getPreviewOutput($lang = null, $default = true) {
        $content = $this->getContent();
        if ($content) {
            if ($class = $this->class) {
                $contentPreview = '<small>Content will be created by <code>' . $class . '</code>.</small>';
            } else {
                $contentPreview = '»' . htmlentities($content->name) . '«';
            }
        } else {
            if ($class = $this->class) {
                $contentPreview = '<small>Provider <code>' . $class . '</code> did not provide content in this context.</small>';
            } else {
                $contentPreview = '<em>Ohne inhalt</em>';
            }
        }
        $contentPreview = '<span style="background-color:white;color:orange; padding:2px 5px;">' . $contentPreview . '</span>';
        if ($content) {
            $content->wrapWithContext($contentPreview);
        }
        return $contentPreview;
    }

    function wrapPreviewWithContext(&$s) {
        $this->wrapWithContext($s);
        $row = $this->getRowContext();
        $row->wrapPreviewWithContext($s);
    }

    function &getLayoutContext() {
        $p = $this->getRowContext();
        if (!$p)
            return self::$_NULL;
        return $p->getLayoutContext();
    }

    static $_NULL = null;

    function &getRowContext($load = true) {
        if ($this->has_parent())
            return $this->get_parent();

        if ($load) {
            try {
                $p = new \WEPPO\Grid\Row($this->row_id);
                $this->set_parent($p);
                return $p;
            } catch (\Exception $e) {
                return self::$_NULL;
            }
        } else {
            throw new \Exception("Cell::getLayoutContext: no parent! not loading");
        }
    }

}
