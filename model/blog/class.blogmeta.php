<?php

namespace WEPPO\Grid;

/**
 * Repräsentiert Blog-Meta-Daten (die im übrigen Sprachunabhängig sind
 * und daher nicht als Content-Param gespeichert werden.)
 * 
 */
class BlogMeta extends \WEPPO\Model\TableRecord {

    static function getTablename() {
        return 'blogmeta';
    }

    static function getCast() {
        return array_merge(parent::getCast(), array(
            'date' => new \WEPPO\Grid\DateConversion(),
        ));
    }

}
