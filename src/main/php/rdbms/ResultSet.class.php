<?php namespace rdbms;

use util\TimeZone;
use lang\Closeable;

/**
 * Result set as returned from the DBConnection::query method
 *
 * Usage (abbreviated example):
 * ```php
 * $r= $conn->query('select news_id, caption, created_at from news');
 * while ($row= $r->next()) {
 *   var_dump($row);
 * }
 * $r->close();
 * ```
 *
 * @test  xp://rdbms.unittest.ResultSetTest
 */
abstract class ResultSet extends \lang\Object implements Closeable, \IteratorAggregate {
  protected $handle, $fields, $tz;
  private $iterator= null;

  /**
   * Constructor
   *
   * @param   var $handle
   * @param   var $fields
   * @param   util.TimeZone $tz
   */
  public function __construct($handle, $fields, TimeZone $tz= null) {
    $this->handle= $handle;
    $this->fields= $fields;
    $this->tz= $tz;
  }

  /** @return bool */
  public function isSuccess() { return false; }

  /** @return int */
  public function affected() { return -1; }

  /**
   * Seek to a specified position within the resultset
   *
   * @param   int $offset
   * @return  bool success
   * @throws  rdbms.SQLException
   */
  public abstract function seek($offset);

  /**
   * Iterator function. Returns a rowset if called without parameter,
   * the fields contents if a field is specified or FALSE to indicate
   * no more rows are available.
   *
   * @param   string $field default NULL
   * @return  var
   */
  public abstract function next($field= null);

  /**
   * Close resultset and free result memory
   *
   * @return  void
   */
  public function close() { }

  /**
   * Returns an iterator
   *
   * @return php.Iterator
   */
  public function getIterator() {
    if (null === $this->iterator) {
      $this->iterator= new ResultSetIterator($this);
    }
    return $this->iterator;
  }

  /**
   * Returns a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'('.\xp::stringOf($this->handle).')@'.\xp::stringOf($this->fields);
  }

  /**
   * Destructor. Ensures `close()` is called.
   */
  public function __destruct() {
    $this->close();
  }
}
