<?php namespace rdbms\criterion;

use rdbms\DBConnection;
use rdbms\Peer;

/**
 * Represents a query criterion to be used in a Criteria query
 *
 * @see      xp://rdbms.Criteria#add
 */
interface Criterion {

  /**
   * Returns the fragment SQL
   *
   * @param   rdbms.DBConnection conn
   * @param   rdbms.Peer peer
   * @return  string
   * @throws  rdbms.SQLStateException
   */
  public function asSql(DBConnection $conn, Peer $peer);
}
