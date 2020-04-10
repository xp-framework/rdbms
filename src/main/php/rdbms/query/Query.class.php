<?php namespace rdbms\query;
use lang\{IllegalArgumentException, IllegalStateException};
use rdbms\{Criteria, Peer};


/**
 * Store complete queries with criteria, method and peer.
 * 
 * Example
 * =======
 * <code>
 *   $dq= new DeleteQuery(Person::getPeer());
 *   
 *   // this query is to only allow the deletion of people who's surname is Maier
 *   $dq->addRestriction(Person::column('surname')->equal('Maier'));
 *   
 *   // will delete Peter Maier in the person table
 *   $dq->withRestriction(Person::column('name')->equal('Peter'))->execute();
 * </code>
 *
 * @see      xp://rdbms.query.SelectQuery
 * @see      xp://rdbms.query.InsertQuery
 * @see      xp://rdbms.query.UpdateQuery
 * @purpose  Base class for SelectQuery, DeleteQuery and UpdateQuery
 */
abstract class Query implements QueryExecutable {
  protected
    $criteria=     null,
    $peer=         null;
  
  /**
   * Constructor
   *
   * @param rdbms.Peer peer optional
   */
  public function __construct(Peer $peer= null) {
    $this->criteria= new Criteria();
    $this->peer= $peer;
  }

  /**
   * set criteria
   *
   * @param  rdbms.Criteria criteria
   */
  public function setCriteria(Criteria $criteria) {
    $this->criteria= $criteria;
  }
  
  /**
   * get criteria
   *
   * @return  rdbms.Criteria
   */
  public function getCriteria() {
    return $this->criteria;
  }
  
  /**
   * set peer
   *
   * @param  rdbms.Peer peer
   */
  public function setPeer(Peer $peer) {
    $this->peer= $peer;
  }
  
  /**
   * get peer
   *
   * @return rdbms.Peer
   */
  public function getPeer() {
    return $this->peer;
  }
  
  /**
   * get connection for peer
   * proxy method for rdbms.Peer::getConnection()
   * if peer is not set Null is returned
   *
   * @return rdbms.DBConnection
   */
  public function getConnection() {
    if (null === $this->peer) return null;
    return $this->peer->getConnection();
  }
  
  /**
   * add a new restriction to the criteria
   *
   * @param  rdbms.criteria.Criterion criterion
   * @return rdbms.Query
   */
  public function addRestriction(\rdbms\criterion\Criterion $criterion) {
    $this->getCriteria()->add($criterion);
    return $this;
  }
  
  /**
   * make copy with added restriction restriction
   *
   * @param  rdbms.criteria.Criterion criterion
   * @return rdbms.Query
   */
  public function withRestriction(\rdbms\criterion\Criterion $criterion) {
    $q= clone($this);
    $q->getCriteria()->add($criterion);
    return $q;
  }
  
}