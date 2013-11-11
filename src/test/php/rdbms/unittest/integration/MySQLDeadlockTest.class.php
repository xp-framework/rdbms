<?php namespace rdbms\unittest\integration;

/**
 * Deadlock test on mysql
 *
 */
class MySQLDeadlockTest extends AbstractDeadlockTest {

  /**
   * Retrieve DSN
   *
   * @return  string
   */
  public function _dsn() {
    return 'mysql';
  }
}
