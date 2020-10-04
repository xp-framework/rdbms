<?php namespace rdbms\unittest\dataset;

use rdbms\finder\Finder;
use rdbms\{Criteria, Statement};
use util\Date;

/** Finder for Job objects */
class JobFinder extends Finder {

  /**
   * Returns the Peer object for this finder
   *
   * @return  rdbms.Peer
   */
  public function getPeer() {
    return Job::getPeer();
  }
  
  /**
   * Finds a job by its primary key
   *
   * @param   int pk the job_id
   * @return  rdbms.Criteria
   */
  #[Finder(kind: ENTITY)]
  public function byPrimary($pk) {
    return new Criteria(['job_id', $pk, EQUAL]);
  }
  
  /**
   * Finds newest jobs
   *
   * @return  rdbms.Criteria
   */
  #[Finder(kind: COLLECTION)]
  public function newestJobs() {
    return (new Criteria())->addOrderBy('valid_from', DESCENDING);
  }

  /**
   * Finds expired jobs
   *
   * @return  rdbms.Criteria
   */
  #[Finder(kind: COLLECTION)]
  public function expiredJobs() {
    return new Criteria(['expire_at', Date::now(), GREATER_THAN]);
  }

  /**
   * Finds jobs with a title similar to the specified title
   *
   * @param   string title
   * @return  rdbms.Criteria
   */
  #[Finder(kind: COLLECTION)]
  public function similarTo($title) {
    return new Statement('select object(j) from job j where title like %s', $title.'%');
  }
}