<?php namespace rdbms\unittest\integration;

/**
 * Deadlock test on PostgreSQL
 *
 */
class PostgreSQLDeadlockTest extends AbstractDeadlockTest {

  /** @return string */
  protected function driverName() { return 'pgsql'; }
}