<?php namespace rdbms\finder;

/**
 * Expects to find exactly 1 row.
 *
 * @see   xp://rdbms.finder.Finder#get
 */
class GetDelegate extends FinderDelegate {

  /**
   * Select implementation
   *
   * @param   rdbms.Criteria criteria
   * @return  rdbms.DataSet
   * @throws  rdbms.finder.FinderException
   * @throws  rdbms.finder.NoSuchEntityException
   */
  public function select($criteria) {
    $peer= $this->finder->getPeer();
    try {
      $it= $peer->iteratorFor($criteria);
    } catch (\rdbms\SQLException $e) {
      throw new FinderException('Failed finding '.$peer->identifier, $e);
    }
    
    // Check for results. If we cannot find anything, throw a NSEE
    if (!$it->hasNext()) {
      throw new NoSuchEntityException(
        'Entity does not exist', 
        new \lang\IllegalStateException('No results for '.$criteria->toString())
      );
    }
    
    // Fetch first value, and if nothing is returned after that, return it,
    // throwing an exception otherwise
    $e= $it->next();
    if ($it->hasNext()) {
      throw new FinderException(
        'Query returned more than one result after '.$e->toString(), 
        new \lang\IllegalStateException('')
      );
    }
    
    return $e;
  }
}