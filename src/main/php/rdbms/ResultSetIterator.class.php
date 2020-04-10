<?php namespace rdbms;

/**
 * Foreach support for results
 *
 * @test xp://rdbms.unittest.ResultSetTest
 */
class ResultSetIterator implements \Iterator {
  const EOF = -1;
  protected $rs, $offset, $current;

  /**
   * Constructor
   *
   * @param   rdbms.ResultSet $rs
   */
  public function __construct($rs) {
    $this->rs= $rs;
    $this->offset= 0;
  }

  /** @return void */
  public function rewind() {
    if (self::EOF === $this->offset) {
      $this->rs->seek(0);
    }

    if (!($this->current= $this->rs->next())) {
      $this->offset= self::EOF;
    } else {
      $this->offset= 0;
    }
  }

  /** @return var */
  public function current() {
    return $this->current;
  }

  /** @return var */
  public function key() {
    return $this->offset;
  }

  /** @return void */
  public function next() {
    if (!($this->current= $this->rs->next())) {
      $this->offset= self::EOF;
    } else {
      $this->offset++;
    }
  }

  /** @return bool */
  public function valid() {
    return $this->offset > self::EOF;
  }
} 