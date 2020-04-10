<?php namespace rdbms;

/**
 * Success result set as returned from the DBConnection::query method
 *
 * Usage (abbreviated example):
 * ```php
 * $r= $conn->query('insert into ...');
 * var_dump($r->affected());
 * ```
 *
 * @test  xp://rdbms.unittest.QuerySucceededTest
 */
class QuerySucceeded extends ResultSet {
  private $affected;

  /**
   * Constructor
   *
   * @param  int $affected
   */
  public function __construct($affected) {
    $this->affected= $affected;
  }

  /** @return bool */
  public function isSuccess() { return true; }

  /** @return int */
  public function affected() { return $this->affected; }

  /**
   * Seek to a specified position within the resultset
   *
   * @param   int $offset
   * @return  bool success
   * @throws  rdbms.SQLException
   */
  public function seek($offset) {
    throw new SQLException('Cannot seek in success results');
  }

  /**
   * Iterator function. Returns a rowset if called without parameter,
   * the fields contents if a field is specified or FALSE to indicate
   * no more rows are available.
   *
   * @param   string $field default NULL
   * @return  var
   */
  public function next($field= null) {
    return false;
  }

  /**
   * Returns an iterator
   *
   * @return php.Iterator
   */
  public function getIterator() {
    return new \ArrayIterator([]);
  }

  /**
   * Returns a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'->'.$this->affected;
  }
}