<?php namespace rdbms\ibase;

use rdbms\ResultSet;


/**
 * Result set
 *
 * @ext      interbase
 * @purpose  Resultset wrapper
 */
class InterbaseResultSet extends ResultSet {

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result, \util\TimeZone $tz= null) {
    $fields= [];
    if (is_resource($result)) {
      for ($i= 0, $num= ibase_num_fields($result); $i < $num; $i++) {
        $field= ibase_field_info($result, $i);
        $fields[$field['name']]= $field['type'];
      }
    }
    parent::__construct($result, $fields, $tz);
  }

  /**
   * Seek
   *
   * @param   int offset
   * @return  bool success
   * @throws  rdbms.SQLException
   */
  public function seek($offset) { 
    if (!ibase_data_seek($this->handle, $offset)) {
      throw new \rdbms\SQLException('Cannot seek to offset '.$offset);
    }
    return true;
  }
  
  /**
   * Iterator function. Returns a rowset if called without parameter,
   * the fields contents if a field is specified or FALSE to indicate
   * no more rows are available.
   *
   * @param   string field default NULL
   * @return  [:var]
   */
  public function next($field= null) {
    if (
      !is_resource($this->handle) ||
      false === ($row= ibase_fetch_assoc($this->handle))
    ) {
      return null;
    }

    foreach (array_keys($row) as $key) {
      if (null === $row[$key] || !isset($this->fields[$key])) continue;
      if ('TIMESTAMP' == $this->fields[$key]) {
        $row[$key]= new \util\Date($row[$key], $this->tz);
      }
    }
    
    if ($field) return $row[$field]; else return $row;
  }

  /**
   * Close resultset and free result memory
   *
   * @return  bool success
   */
  public function close() { 
    if (!$this->handle) return;
    $r= ibase_free_result($this->handle);
    $this->handle= null;
    return $r;
  }
}