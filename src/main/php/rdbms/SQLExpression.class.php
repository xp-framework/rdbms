<?php namespace rdbms;

/**
 * An SQL expression. Implemented by Criteria and Statement.
 */
interface SQLExpression {
  
  /**
   * Test if the expression is a projection
   *
   * @return  bool
   */
  public function isProjection();

  /**
   * Test if the expression is a join
   *
   * @return  bool
   */
  public function isJoin();

  /**
   * Executes an SQL SELECT statement
   *
   * @param   rdbms.DBConnection $conn
   * @param   rdbms.Peer $peer
   * @param   rdbms.join.Joinprocessor $jp optional
   * @param   bool $buffered default TRUE
   * @return  rdbms.ResultSet
   */
  public function executeSelect(DBConnection $conn, Peer $peer, $jp= null, $buffered= true);
}