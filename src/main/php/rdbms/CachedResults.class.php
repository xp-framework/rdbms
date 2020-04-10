<?php namespace rdbms;

class CachedResults implements \util\XPIterator {
  private $_hash, $_key;

  /**
   * Constructor
   *
   * @param   [:var] hash
   */
  public function __construct(array $hash) {
    $this->_hash= $hash;
    reset($this->_hash);
    $this->_key= key($this->_hash);
  }

  /**
   * Returns true if the iteration has more elements. (In other words, 
   * returns true if next would return an element rather than throwing 
   * an exception.)
   *
   * @return  bool
   */
  public function hasNext() {
    return null !== $this->_key;
  }
  
  /**
   * Returns the next element in the iteration.
   *
   * @return  var
   * @throws  util.NoSuchElementException when there are no more elements
   */
  public function next() {
    if (is_null($this->_key)) throw new NoSuchElementException('No more elements');
    $oldkey= $this->_key;
    next($this->_hash);
    $this->_key= key($this->_hash);
    return $this->_hash[$oldkey];
  }
}