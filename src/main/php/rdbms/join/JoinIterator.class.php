<?php namespace rdbms\join;

/**
 * Class of the join api iterator for join results
 *
 * @see   xp://rdbms.join.JoinProcessor
 * @see   xp://rdbms.Criteria#getSelectQueryString
 * @see   xp://rdbms.Peer#iteratorFor
 */
class JoinIterator implements \util\XPIterator, JoinExtractable {
  private
    $resultObj= null,
    $record= [],
    $obj= null,
    $obj_key= '',
    $jp= null,
    $rs= null;

  /**
   * Constructor
   *
   * @param   rdbms.join.JoinProcessor jp
   * @param   rdbms.ResultSet rs
   */
  public function __construct(JoinProcessor $jp, \rdbms\ResultSet $rs) {
    $this->jp= $jp;
    $this->rs= $rs;
    $this->record= $this->rs->next();
  }

  /**
   * Returns true if the iteration has more elements. (In other words, 
   * returns true if next would return an element rather than throwing 
   * an exception.)
   *
   * @return  bool
   */
  public function hasNext() {
    return (null !== $this->record);
  }
  
  /**
   * Returns the next element in the iteration.
   *
   * @return  var
   * @throws  util.NoSuchElementException when there are no more elements
   */
  public function next() {
    if (!$this->record) throw new \util\NoSuchElementException('No more elements');
    do {
      $this->jp->joinpart->extract($this, $this->record, JoinProcessor::FIRST);
      if (!is_null($this->resultObj)) {
        $r= $this->resultObj;
        $this->resultObj= null;
        return $r;
      }
    } while ($this->record= $this->rs->next());
    return $this->obj;
  }

  /**
   * set "in construct" result object
   *
   * @param   string role
   * @param   string unique key
   * @param   lang.Object obj
   */
  public function setCachedObj($role, $key, $obj) {
    $this->resultObj= $this->obj;
    $this->obj= $obj;
    $this->obj_key= $key;
  }

  /**
   * get an object from the found objects
   *
   * @param   string role
   * @param   string unique key
   * @throws  util.NoSuchElementException
   */
  public function getCachedObj($role, $key) {
    if ($this->obj_key && $this->obj_key != $key) throw new \util\NoSuchElementException('object under construct does not exist - maybe you should sort your query');
    return $this->obj;
  }

  /**
   * test an object for existance in the found objects
   *
   * @param   string role
   * @param   string unique key
   */
  public function hasCachedObj($role, $key) {
    return ($this->obj_key == $key);
  }

  /**
   * mark a role as cached
   *
   * @param   string role
   */
  public function markAsCached($role) {}
}