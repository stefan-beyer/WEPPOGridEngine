<?php

namespace WEPPO\Grid;

/**
 * TableRecord, für das man in der Spalte 'class'
 * explizit eine spezialisierte (Unter-)Klasse (von ExplicitClassTableRecord)
 * angeben kann.
 * 
 * Hierzu wird lediglich _dynamicBindResults überschrieben, worin die Objekte
 * aus dem DB-Result erzeugt werden.
 * 
 * @TODO der größte Teil der Funktion _dynamicBindResults wird aus dem Original
 * wiederholt. Das ist Bääh!
 */
class ExplicitClassTableRecord extends \WEPPO\Model\TableRecord {

    /**
     * This helper method takes care of prepared statements' "bind_result method
     * , when the number of variables to pass is unknown.
     *
     * @param mysqli_stmt $stmt Equal to the prepared statement object.
     *
     * @return array The results of the SQL fetch.
     */
    static protected function _dynamicBindResults(\mysqli_stmt $stmt, $createObjects = true) {
        $parameters = array();
        $results = array();

        $meta = $stmt->result_metadata();

        // if $meta is false yet sqlstate is true, there's no sql error but the query is
        // most likely an update/insert/delete which doesn't produce any results
        if (!$meta && $stmt->sqlstate) {
            return array();
        }
        // das hier bereitet ein result array vor in das bei statmt->fetch die werte geschrieben werden
        $row = array();
        while ($field = $meta->fetch_field()) {
            $row[$field->name] = null;
            $parameters[] = & $row[$field->name];
        }


        // avoid out of memory bug in php 5.2 and 5.3
        // https://github.com/joshcam/PHP-MySQLi-Database-Class/pull/119
        if (\version_compare(\phpversion(), '5.4', '<'))
            $stmt->store_result();

        \call_user_func_array(array($stmt, 'bind_result'), $parameters);



        if (static::createObjects()) {
            // $classname = static::getStaticClass();
            $std_classname = static::getStaticClass();
            while ($stmt->fetch()) {
                $classname = $row['class'] ? $row['class'] : $std_classname;
                if (class_exists($classname)) {
                    $x = new $classname();
                    $x->assign($row);
                    static::$count += count($row); // ????
                    \array_push($results, $x);
                } else {
                    trigger_error('class ' . $classname . ' not found in \WEPPO\Grid\ExplicitClassTableRecord', E_USER_ERROR);
                }
            }

            if (static::$doResolve /* && !empty(static::$resolveForeinFields) */) {
                foreach ($results as &$r) {
                    $r->resolveForeinFields();
                }
            }
        } else {
            while ($stmt->fetch()) {
                $x = array();
                foreach ($row as $key => $val) {
                    $x[$key] = $val;
                }
                static::$count++;
                \array_push($results, $x);
            }
        }

        static::createObjects(true);

        return $results;
    }

}

