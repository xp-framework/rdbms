<?php namespace rdbms\mysql;

use rdbms\ResultSet;


/**
 * Result set
 *
 * @ext      mysql
 * @purpose  Resultset wrapper
 */
class MySQLResultSet extends ResultSet {

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result, \util\TimeZone $tz= null) {
    $fields= [];
    if (is_resource($result)) {
      for ($i= 0, $num= mysql_num_fields($result); $i < $num; $i++) {
        $field= mysql_fetch_field($result, $i);
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
    if (!mysql_data_seek($this->handle, $offset)) {
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
      false === ($row= mysql_fetch_assoc($this->handle))
    ) {
      return false;
    }
    
    foreach (array_keys($row) as $key) {
      if (null === $row[$key] || !isset($this->fields[$key])) continue;
      switch ($this->fields[$key]) {
        case 'timestamp':
          if (strlen($row[$key]) == 14) {
            $time= sscanf ((string)$row[$key], '%04s%02s%02s%02s%02s%02s');
            $row[$key]= new \util\Date(mktime(
              $time[3],
              $time[4],
              $time[5],
              $time[1],
              $time[2],
              $time[0]
            ), $this->tz);
            
            break;
          }
            
        case 'datetime':
        case 'date':
          $row[$key]= new \util\Date($row[$key], $this->tz);
          break;
          
        case 'int':
          if ($row[$key] <= PHP_INT_MAX && $row[$key] >= -PHP_INT_MAX- 1) {
            settype($row[$key], 'integer');
          } else {
            settype($row[$key], 'double');
          }
          break;

        case 'bit':
          settype($row[$key], 'integer');
          break;
          
        case 'real':
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
    $r= @mysql_free_result($this->handle);
    $this->handle= null;
    return $r;
  }
}
