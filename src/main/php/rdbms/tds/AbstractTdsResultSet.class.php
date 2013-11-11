<?php namespace rdbms\tds;

use rdbms\ResultSet;


/**
 * Abstract base class
 *
 */
abstract class AbstractTdsResultSet extends ResultSet {
 
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
      $return[$info['name']] = $record[$i];
    }
    return null === $field ? $return : $return[$field];
  } 
}
