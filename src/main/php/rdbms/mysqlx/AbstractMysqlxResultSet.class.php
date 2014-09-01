<?php namespace rdbms\mysqlx;

use rdbms\ResultSet;


/**
 * Abstract base class
 *
 */
abstract class AbstractMysqlxResultSet extends ResultSet {
 
  /**
   * Returns a record
   *
   * @param   [:var] record
   * @param   string field
   * @return  [:var] record
   */
  protected function record($record, $field= null) {
    $return= array();
    foreach ($this->fields as $i => $info) {
      $type= $info['type'];
      $value= $record[$i];

      if (null === $value) {
        $return[$info['name']]= null;
        continue;
      }

      switch ($type) {
        case 10:    // DATE
        case 11:    // TIME
        case 12:    // DATETIME
        case 14:    // NEWDATETIME
        case 7:     // TIMESTAMP
          $return[$info['name']]= null === $value || '0000-00-00 00:00:00' === $value ? null : \util\Date::fromString($value, $this->tz);
          break;
        
        case 8:     // LONGLONG
        case 3:     // LONG
        case 9:     // INT24
        case 2:     // SHORT
        case 1:     // TINY
        case 16:    // BIT
          if ($value <= PHP_INT_MAX && $value >= -PHP_INT_MAX- 1) {
            $return[$info['name']]= (int)$value;
          } else {
            $return[$info['name']]= (double)$value;
          }
          break;
          
        case 4:     // FLOAT
        case 5:     // DOUBLE
        case 0:     // DECIMAL
        case 246:   // NEWDECIMAL
          $return[$info['name']]= (double)$value;
          break;

        case 253:   // CHAR
          $return[$info['name']]= (string)$value;
          break;

        default:
          $return[$info['name']]= $value;
      }
    }
    return null === $field ? $return : $return[$field];
  } 
}
