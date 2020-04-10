<?php namespace rdbms\finder;

/**
 * Expects to find any amount of rows
 *
 * @see   xp://rdbms.finder.Finder#find
 */
class FindAllDelegate extends FinderDelegate {

  /**
   * Select implementation
   *
   * @param   rdbms.Criteria criteria
   * @return  rdbms.DataSet[]
   * @throws  rdbms.finder.FinderException
   */
  public function select($criteria) {
    $peer= $this->finder->getPeer();
    try {
      return $peer->doSelect($criteria);
    } catch (\rdbms\SQLException $e) {
      throw new FinderException('Failed finding '.$peer->identifier, $e);
    }
  }
}