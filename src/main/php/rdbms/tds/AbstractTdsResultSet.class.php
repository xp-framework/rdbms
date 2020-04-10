<?php namespace rdbms\tds;

/**
 * Abstract base class
 *
 */
abstract class AbstractTdsResultSet extends \rdbms\ResultSet {
 
  /**
   * Returns a record
   *
   * @param   [:var] record
   * @param   string field
   * @return  [:var] record
   */
  protected function record($record, $field= null) {
    $return= [];
    foreach ($this->fields as $i => $info) {
      $return[$info['name']] = $record[$i];
    }
    return null === $field ? $return : $return[$field];
  } 
}