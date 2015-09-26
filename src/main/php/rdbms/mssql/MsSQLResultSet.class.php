<?php namespace rdbms\mssql;

use rdbms\ResultSet;


/**
 * Result set
 *
 * @ext      mssql
 * @purpose  Resultset wrapper
 */
class MsSQLResultSet extends ResultSet {

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result, \util\TimeZone $tz= null) {
    $fields= [];
    if (is_resource($result)) {
      for ($i= 0, $num= mssql_num_fields($result); $i < $num; $i++) {
        $field= mssql_fetch_field($result, $i);
        $fields[$field->name]= $field->type;
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
    if (!mssql_data_seek($this->handle, $offset)) {
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
   * @return  var
   */
  public function next($field= null) {
    if (
      !is_resource($this->handle) ||
      false === ($row= mssql_fetch_assoc($this->handle))
    ) {
      return false;
    }

    foreach ($row as $key => $value) {
      if (null === $value || !isset($this->fields[$key])) continue;
      
      switch ($this->fields[$key]) {
        case 'datetime': {
          $row[$key]= new \util\Date($value, $this->tz);
          break;
        }
        
        case 'numeric': {
          if (false !== strpos($value, '.')) {
            settype($row[$key], 'double');
            break;
          }
          // Fallthrough intentional
        }
          
        case 'int': {
          if ($value <= PHP_INT_MAX && $value >= -PHP_INT_MAX- 1) {
            settype($row[$key], 'integer');
          } else {
            settype($row[$key], 'double');
          }
          break;
        }
        
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
    $r= mssql_free_result($this->handle);
    $this->handle= null;
    return $r;
  }
}
