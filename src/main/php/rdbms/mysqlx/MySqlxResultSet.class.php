<?php namespace rdbms\mysqlx;



/**
 * Result set
 *
 * @purpose  Resultset wrapper
 */
class MySQLxResultSet extends \AbstractMySQLxResultSet {

  /**
   * Seek
   *
   * @param   int offset
   * @return  bool success
   * @throws  rdbms.SQLException
   */
  public function seek($offset) { 
    throw new \rdbms\SQLException('Cannot seek to offset '.$offset);
  }
  
  /**
   * Iterator function. Returns a rowset if called without parameter,
   * the fields contents if a field is specified or FALSE to indicate
   * no more rows are available.
   *
   * @param   string field default NULL
   * @return  var
   */
  public function next($field= null) {
    if (null === $this->handle || null === ($record= $this->handle->fetch($this->fields))) {
      $this->handle= null;
      return false;
    }
    
    return $this->record($record, $field);
  }
  
  /**
   * Close resultset and free result memory
   *
   * @return  bool success
   */
  public function close() { 
    $this->handle= null;
    return true;
  }
}
