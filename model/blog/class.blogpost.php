<?php

namespace WEPPO\Grid;

/**
 * Spezialisierte Content-Klasse für Blog-Inhalte.
 * 
 *  - Einige Content-Params werden besonders behandelt (BlogPost::$managedParams)
 *  - Tags sind in einer extra Tabelle, sind mehrsprachig und können per
 *    loadTags() geladen oder per whereTag(..) nach ihnen gefiltert werden.
 *  - Zusätzlich wird die Tabelle BlogMeta eingebunden, die sprachunabhängige
 *    Metadaten enhält (BlogPost::$meta_fields). Meta-Daten werden direkt mitgeladen; Tags nicht.
 *  - Das Template bekommt mit prepareTemplate() eine besondere Vorbereitung.
 */
class BlogPost extends \WEPPO\Grid\Content {

    //static $metaJoinType = 'INNER';
    static $metaJoinType = 'LEFT';
    static $tagConcat = 'GROUP_CONCAT(DISTINCT bt.tag ORDER BY bt.tag ASC SEPARATOR ",")';
    static $managedParams = array('title', 'text', 'intro', 'picture');
    static $meta_fields = array('author', 'date', 'visible');

    static function getCast() {
        return array_merge(parent::getCast(), array(
            'date' => new \WEPPO\Grid\DateConversion(),
            'tags' => new \WEPPO\Grid\ListConversion(), // nicht mehr verwendet?
        ));
    }

    static function get($numRows = null, $columns = '*') {
        static::prepareGet($columns);
        return parent::get($numRows, $columns);
    }

    public function load($cols = '*') {
        static::prepareGet($cols);
        return parent::load($cols);
    }

    static function whereTag($tag, $lang = null) {
        $subq = 'SELECT tag from ' . self::getPrefix() . 'blogtags bt where bt.content_id = gridcontents1.id';
        if ($lang !== null) {
            $subq .= ' AND bt.lang = "' . static::escape($lang) . '"';
        }
        static::where('"' . static::escape($tag) . '" IN (' . $subq . ')');
    }

    private static function prepareGet(&$columns) {
        if ($columns === '*') {
            $columns = 'gridcontents1.*, bm.*, gridcontents1.id AS id, bm.id AS meta_id'; //, '.static::$tagConcat.' AS tags';
        }

        static::join('blogmeta bm', 'bm.content_id = gridcontents1.id', static::$metaJoinType);
        static::where('class', CONTENT_CLASS_BLOG);
    }

    static function createFromName($name, $visible = true) {

        static::where('class', CONTENT_CLASS_BLOG);
        static::where('name', $name);
        if ($visible !== null) {
            static::where('visible', $visible ? 1 : 0);
        }

        $ret = static::get(1);
        if (isset($ret[0])) {
            return $ret[0];
        }
        return null;
    }

    function prepareTemplate($lang) {
        //echo "Blog::prepareTemplate ";
        parent::prepareTemplate($lang);

        $template = $this->getTemplate($lang);
        if ($template) {
            foreach (static::$meta_fields as $mf) {
                if (property_exists($this, $mf)) {
                    $template->set($mf, $this->{$mf});
                }
            }
            $post_url = $template->get('baseURL') . 'article/' . identifier($this->name);
            $template->set('url', $post_url);
            $template->set('tags', property_exists($this, 'tags') ? $this->tags : null);
        }
    }

    function getPreviewOutput(&$cell, $lang) {
        $this->prepareTemplate($lang);
        return parent::getPreviewOutput($cell, $lang);
    }

    /**
     * $lang = null: alle sprachen werden geladen
     */
    function loadTags($lang = null) {
        if ($this->noTranslation) {
            $lang = \i18n\i18n::$defaultLanguage;
        }

        if (!property_exists($this, 'tags')) {
            \WEPPO\Grid\BlogTag::where('content_id', $this->id);
            if ($lang !== null) {
                \WEPPO\Grid\BlogTag::where('lang', $lang);
            }
            \WEPPO\Grid\BlogTag::orderBy('tag', 'ASC');
            $tags = \WEPPO\Grid\BlogTag::get();
            $this->tags = array();
            foreach ($tags as &$t) {
                $this->tags[] = $t->tag;
            }
        }
        return $this->tags;
    }

}
