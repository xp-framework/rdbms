<?php namespace rdbms\unittest\integration;

use unittest\Assert;
/**
 * Deadlock test on PostgreSQL
 *
 */
class PostgreSQLDeadlockTest extends AbstractDeadlockTest {

  /** @return string */
  protected function driverName() { return 'pgsql'; }
}