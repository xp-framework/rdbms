<?php namespace rdbms\pgsql;

use rdbms\ResultSet;

/**
 * Result set
 *
 * @ext   pgsql
 */
class PostgreSQLResultSet extends ResultSet {

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result, \util\TimeZone $tz= null) {
    $fields= [];
    if (is_resource($result)) {
      for ($i= 0, $num= pg_num_fields($result); $i < $num; $i++) {
        $fields[pg_field_name($result, $i)]= pg_field_type($result, $i);
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
    if (!pg_data_seek($this->handle, $offset)) {
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
      false === ($row= pg_fetch_assoc($this->handle))
    ) {
      return null;
    }
    
    foreach ($row as $key => $value) {
      switch ($this->fields[$key]) {
        case 'date':
        case 'time':
        case 'timestamp':
          $row[$key]= new \util\Date($value, $this->tz);
          break;

        case 'bool':
          if ($value === 't') {
            $row[$key]= true;
          } else if ($value === 'f') {
            $row[$key]= false;
          } else {
            throw new \rdbms\SQLException('Boolean field carries illegal value: "'.$value.'"');
          }

          break;
          
        case 'int2':
        case 'int4':
        case 'int8':
          settype($row[$key], 'integer'); 
          break;
          
        case 'float4':
        case 'float8':
        case 'numeric':
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
    $r= pg_free_result($this->handle);
    $this->handle= null;
    return $r;
  }
}
