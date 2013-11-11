<?php namespace rdbms\sqlite3;

use rdbms\ResultSet;


/**
 * Result set
 *
 * @ext      sqlite
 * @purpose  Resultset wrapper
 */
class SQLite3ResultSet extends ResultSet {

  /**
   * Constructor
   *
   * @param   resource handle
   */
  public function __construct($result) {
    $fields= array();
    if ($result instanceof \SQLite3Result) {
      for ($i= 0, $num= $result->numColumns(); $i < $num; $i++) {
        $fields[$result->columnName($i)]= $result->columnType($i); // Types are unknown
      }
    }
    parent::__construct($result, $fields);
  }

  /**
   * Seek
   *
   * @param   int offset
   * @return  bool success
   * @throws  rdbms.SQLException
   */
  public function seek($offset) {
    if (!sqlite_seek($this->handle, $offset)) {
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
      !$this->handle instanceof \SQLite3Result ||
      false === ($row= $this->handle->fetchArray(SQLITE3_ASSOC))
    ) {
      return false;
    }

    foreach ($row as $key => $value) {
      if (null === $value || !isset($this->fields[$key])) continue;
      
      switch ($value{0}) {
        case "\2":
          $row[$key]= \util\Date::fromString(substr($value, 1));
          break;

        case "\3":
          $row[$key]= intval(substr($value, 1));
          break;

        case "\4":
          $row[$key]= floatval(substr($value, 1));
          break;
      }
    }
    
    if ($field) return $value; else return $row;
  }

  /**
   * Close resultset and free result memory
   *
   * @return  bool success
   */
  public function close() { 
    if ($this->handle instanceof \SQLite3Result) return;
    $r= $this->handle->finalize();
    $this->handle= null;
    return $r;
  }
}
