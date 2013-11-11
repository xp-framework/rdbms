<?php namespace rdbms\query;


/**
 * store complete queries with criteria, method and peer
 *
 * @see      xp://rdbms.query.Query
 * @purpose  rdbms.query
 */
class UpdateQuery extends Query {

  /**
   * execute query without set operation
   *
   * @param  var[] values
   * @return int number of affected rows
   * @throws lang.IllegalStateException
   */
  public function execute($values= null) {
    if (is_null($this->peer))      throw new \lang\IllegalStateException('no peer set');
    if ($this->criteria->isJoin()) throw new \lang\IllegalStateException('can not update into joins');
    return $this->peer->doUpdate($values, $this->criteria);
  }
  
}
