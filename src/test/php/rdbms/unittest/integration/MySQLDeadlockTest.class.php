<?php namespace rdbms\unittest\integration;

/**
 * Deadlock test on mysql
 *
 */
class MySQLDeadlockTest extends AbstractDeadlockTest {

  /** @return string */
  protected function driverName() { return 'mysql'; }
}
