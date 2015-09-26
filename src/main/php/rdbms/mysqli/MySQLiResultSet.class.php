<?php namespace rdbms\mysqli;

use rdbms\ResultSet;


/**
 * Result set
 *
 * @ext      mysqli
 * @purpose  Resultset wrapper
 */
class MySQLiResultSet extends ResultSet {

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result, \util\TimeZone $tz= null) {
    $fields= [];
    if (is_object($result)) {
      while ($field= $result->fetch_field()) {
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
    if (!mysqli_data_seek($this->handle, $offset)) {
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
      !is_object($this->handle) ||
      null === ($row= mysqli_fetch_assoc($this->handle))
    ) {
      return false;
    }
    
    foreach (array_keys($row) as $key) {
      if (null === $row[$key] || !isset($this->fields[$key])) continue;
      switch ($this->fields[$key]) {
        case MYSQLI_TYPE_DATETIME:
        case MYSQLI_TYPE_DATE:
        case MYSQLI_TYPE_TIME:
        case MYSQLI_TYPE_TIMESTAMP:
        case MYSQLI_TYPE_NEWDATE:
          $row[$key]= '0000-00-00 00:00:00' === $row[$key] ? null : new \util\Date($row[$key], $this->tz);
          break;
          
        case MYSQLI_TYPE_LONGLONG:
        case MYSQLI_TYPE_LONG:
        case MYSQLI_TYPE_INT24:
        case MYSQLI_TYPE_SHORT:
        case MYSQLI_TYPE_TINY:
        case MYSQLI_TYPE_BIT:
          if ($row[$key] <= PHP_INT_MAX && $row[$key] >= -PHP_INT_MAX- 1) {
            settype($row[$key], 'integer');
          } else {
            settype($row[$key], 'double');
          }
          break;

        case MYSQLI_TYPE_FLOAT:
        case MYSQLI_TYPE_DOUBLE:
        case MYSQLI_TYPE_DECIMAL:
        case MYSQLI_TYPE_NEWDECIMAL:
          settype($row[$key], 'double'); 
          break;
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
    $r= mysqli_free_result($this->handle);
    $this->handle= null;
    return $r;
  }
}
