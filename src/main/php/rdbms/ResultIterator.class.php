<?php namespace rdbms;

use util\NoSuchElementException;

/**
 * Iterator over a resultset
 *
 * @test  xp://net.xp_framework.unittest.rdbms.DataSetTest
 * @see   xp://rdbms.Peer
 */
class ResultIterator implements \util\XPIterator {
  public
    $_rs         = null,
    $_identifier = '',
    $_record     = null;

  /**
   * Constructor
   *
   * @param   rdbms.ResultSet rs
   * @param   string identifier
   * @see     xp://rdbms.Peer#iteratorFor
   */
  public function __construct($rs, $identifier) {
    $this->_rs= $rs;
    $this->_identifier= $identifier;
  }

  /**
   * Returns true if the iteration has more elements. (In other words, 
   * returns true if next would return an element rather than throwing 
   * an exception.)
   *
   * @return  bool
   */
  public function hasNext() {

    // Check to see if we have fetched a record previously. In this case,
    // short-cuircuit this to prevent hasNext() from forwarding the result
    // pointer every time we call it.
    if ($this->_record) return true;

    $this->_record= $this->_rs->next();
    return !empty($this->_record);
  }
  
  /**
   * Returns the next element in the iteration.
   *
   * @return  rdbms.DataSet
   * @throws  util.NoSuchElementException when there are no more elements
   */
  public function next() {
    if (null === $this->_record) {
      $this->_record= $this->_rs->next();
      // Fall through
    }
    if (null === $this->_record) {
      throw new NoSuchElementException('No more elements');
    }
    
    // Create an instance and set the _record member to NULL so that
    // hasNext() will fetch the next record.
    $instance= new $this->_identifier($this->_record);
    $this->_record= null;
    return $instance;
  }
} 
