<?php namespace rdbms\unittest\integration;

/**
 * Deadlock test on PostgreSQL
 *
 */
class PostgreSQLDeadlockTest extends AbstractDeadlockTest {

  /**
   * Retrieve DSN
   *
   * @return  string
   */
  public function _dsn() {
    return 'pgsql';
  }
}
