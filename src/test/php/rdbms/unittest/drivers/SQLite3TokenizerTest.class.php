<?php namespace rdbms\unittest\drivers;
 
/**
 * Test tokenizers for SQLite3 connections
 *
 * @see   xp://rdbms.sqlite3.SQLite3Connection
 * @see   xp://net.xp_framework.unittest.rdbms.TokenizerTest
 */
class SQLite3TokenizerTest extends \rdbms\unittest\TokenizerTest {

  /**
   * Sets up a Database Object for the test
   *
   * @return  rdbms.DBConnection
   */
  protected function fixture() {
    return new \rdbms\sqlite3\SQLite3Connection(new \rdbms\DSN('sqlite://localhost/'));
  }
}
