<?php

namespace WEPPO\Grid;

/**
 * (Nur-)Datum-Konvertierung für Datenbank...
 * Konvertiert zu DateTime mit Zeit auf 0:0:0 gesetzt.
 */
class DateConversion implements \WEPPO\Model\DBCastInterface2 {

    function __construct() {
        
    }

    function parse($str) {
        $dt = new \DateTime($str);
        $dt->setTime(0, 0, 0);
        return $dt;
    }

    function isValid($v) {
        return !!$v;
    }

    function toString($v) {
        if (!$v) {
            return '';
        }
        return $v->format('Y-m-d');
    }

}


/**
 * Zeit/Datum-Konvertierung für Datenbank...
 * Konvertiert zu DateTime.
 */
class DateTimeConversion implements \WEPPO\Model\DBCastInterface2 {

    function __construct() {
        
    }

    function parse($str) {
        $dt = new \DateTime($str);
        return $dt;
    }

    function isValid($v) {
        return !!$v;
    }

    function toString($v) {
        if (!$v) {
            return '';
        }
        return $v->format('Y-m-d H:i:s');
    }

}


/**
 * Listen-Konvertierung für Datenbank...
 * Konvertiert zu Array.
 * Hiermit kann eine Datensatz-Spalte eine Liste von Werten enthalten,
 * die hiermit in ein Array umgewandelt werden und umgekehrt.
 */
class ListConversion implements \WEPPO\Model\DBCastInterface2 {

    var $separator;

    function __construct($t = ',') {
        $this->separator = $t;
    }

    function parse($str) {
        if (empty(trim($str))) {
            return array();
        }
        $l = explode($this->separator, $str);
        $l = array_map('trim', $l);
        return $l;
    }

    function isValid($v) {
        return is_array($v);
    }

    function toString($v) {
        if (!$v || !is_array($v)) {
            return '';
        }
        return implode($this->separator, $v);
    }

}
