<?php namespace rdbms\unittest\drivers;
 
/**
 * Test tokenizers for PostgreSQL connections
 *
 * @see   xp://rdbms.pgsql.PostgreSQLConnection
 * @see   xp://net.xp_framework.unittest.rdbms.TokenizerTest
 */
class PostgreSQLTokenizerTest extends \rdbms\unittest\TokenizerTest {

  /**
   * Sets up a Database Object for the test
   *
   * @return  rdbms.DBConnection
   */
  protected function fixture() {
    return new \rdbms\pgsql\PostgreSQLConnection(new \rdbms\DSN('pgsql://localhost/'));
  }

  #[@test]
  public function labelToken() {
    $this->assertEquals(
      'select * from "order"',
      $this->fixture->prepare('select * from %l', 'order')
    );
  }
}