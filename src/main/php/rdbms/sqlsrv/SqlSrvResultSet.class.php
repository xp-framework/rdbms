<?php namespace rdbms\sqlsrv;

use rdbms\ResultSet;


/**
 * Result set
 *
 * @ext      sqlsrv
 * @purpose  Resultset wrapper
 */
class SqlSrvResultSet extends ResultSet {
  protected static $precision;

  static function __static() {
    self::$precision= ini_get('precision');
  }

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result, \util\TimeZone $tz= null) {
    $fields= [];
    if (is_resource($result)) {
      foreach (sqlsrv_field_metadata($result) as $meta) {
        $fields[$meta['Name']]= $meta;
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
    if (!sqlsrv_data_seek($this->handle, $offset)) {
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
      !is_array($row= sqlsrv_fetch_array($this->handle, SQLSRV_FETCH_ASSOC))
    ) {
      return null;
    }

    foreach ($row as $key => $value) {
      if (null === $value || !isset($this->fields[$key])) continue;
      
      if ($value instanceof \DateTime) {
        $row[$key]= new \util\Date($value);
      } else switch ($this->fields[$key]['Type']) {
        case -9: // SQLSRV_SQLTYPE_DATETIME, SQLSRV_SQLTYPE_SMALLDATETIME
          $row[$key]= new \util\Date($row[$key]); 
          break;

        case 2:  // SQLSRV_SQLTYPE_NUMERIC
          if (strlen($row[$key]) > self::$precision) {
            break;
          } else if ($this->fields[$key]['Scale'] > 0) {
            settype($row[$key], 'double');
            break;
          }
          // Fall through intentionally
        
        case 4:  // SQLSRV_SQLTYPE_INT
          if ($value <= PHP_INT_MAX && $value >= -PHP_INT_MAX- 1) {
            settype($row[$key], 'integer');
          } else {
            settype($row[$key], 'double');
          }
          break;

        case 7: case 3: // SQLSRV_SQLTYPE_REAL, SQLSRV_SQLTYPE_MONEY
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
    $r= sqlsrv_free_stmt($this->handle);
    $this->handle= null;
    return $r;
  }
}