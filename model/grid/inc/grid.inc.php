<?php

namespace WEPPO\Grid;

require_once GRID_MODEL_PATH . '/inc/traits.inc.php';
require_once GRID_MODEL_PATH . '/inc/class.Conversions.php';
require_once GRID_MODEL_PATH . '/inc/class.ExplicitClassTableRecord.php';

/**
 * String als Identifier aufbereiten: nicht erlaubte Zeichen durch _ ersetzen.
 * 
 * @param string $string
 * @return string
 */
function identifier($string) {
    $string = preg_replace('/[^a-z0-9._\-]/i', '_', $string);
    return $string;
}

/**
 * CSS-Class-Name für die verschiedenen Spaltenbreiten ermitteln.
 * 
 * @staticvar array $sizeclasses
 * @param integer $size
 * @return string
 */
function getSizeClass($size) {
    static $sizeclasses = array(
        1 => 'one',
        2 => 'two',
        3 => 'three',
        4 => 'four',
        5 => 'five',
        6 => 'six',
        7 => 'seven',
        8 => 'eight',
        9 => 'nine',
        10 => 'ten',
        11 => 'eleven',
        12 => 'twelve',
    );
    if (isset($sizeclasses[$size]))
        $sizeclass = $sizeclasses[$size];
    else
        $sizeclass = 'one';
    return $sizeclass;
}

/**
 * Holt direkt einen Parameter (ContentParam) aus einem Content-Objekt in der
 * gewünschten Sprache. (Das macht sonst ein Template beim Rendern bei
 * $template->get(...).) Dabei muss dem Content kein Template zu geordnet sein.
 * 
 * Das kann dazu genutzt werden, Inhalte mehrsprachig abzulegen und an belieber
 * stelle abzurufen.
 * 
 * Die Content-Objekte werden gecached, um wahrscheinlich auftauchende mehrfache
 * Aufrufe für den selben Content zu beschleunigen.
 * 
 * @staticvar array $cache
 * @param string $cname Content-Name
 * @param strng $pname  Parameter-Name
 * @param string $lang  Sprache ('de', 'en' etc.)
 * @param int $ctype    Content-Typ (Content Name ist nur zusammen mit Typ eindeutig) @default CONTENT_CLASS_PAGE
 * @return string
 */
function getContentParam($cname, $pname, $lang, $ctype = CONTENT_CLASS_PAGE) {
    static $cache = array();
    if (isset($cache[$ctype . ':' . $cname])) {
        $content = $cache[$ctype . ':' . $cname];
    } else {
        $content = Content::createFromName($cname, $ctype);
        $cache[$ctype . ':' . $cname] = $content;
    }
    if ($content) {
        $tmpl = $content->getTemplate($lang, false); # template muss nicht existieren
        return $tmpl->get($pname);
    }
    return '';
}



