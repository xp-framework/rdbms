<?php namespace rdbms\unittest\integration;

class PostgreSQLDeadlockTest extends AbstractDeadlockTest {
  protected static $DRIVER= 'pgsql';
}