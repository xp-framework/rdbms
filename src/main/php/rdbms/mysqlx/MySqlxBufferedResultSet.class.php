<?php namespace rdbms\mysqlx;

/**
 * Result set
 *
 * @test  xp://net.xp_framework.unittest.rdbms.mysql.MySqlxBufferedResultSetTest
 */
class MySqlxBufferedResultSet extends AbstractMysqlxResultSet {
  protected $records= [];
  protected $offset= 0;
  protected $length= 0;

  /**
   * Constructor
   *
   * @param   var $result
   * @param   [:var] $fields
   * @param   ?util.TimeZone $tz
   */
  public function __construct($result, $fields, $tz= null) {
    parent::__construct($result, $fields, $tz);
    while (null !== ($record= $this->handle->fetch($this->fields))) {
      $this->records[]= $record;
    }
    $this->length= sizeof($this->records);
  }
    
  /**
   * Seek
   *
   * @param   int offset
   * @return  bool success
   * @throws  rdbms.SQLException
   */
  public function seek($offset) { 
    if ($offset < 0 || $offset >= $this->length) {
      throw new \rdbms\SQLException('Cannot seek to offset '.$offset.', out of bounds');
    }
    $this->offset= $offset;
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
    if ($this->offset >= $this->length) return null;
    
    return $this->record($this->records[$this->offset++], $field);
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