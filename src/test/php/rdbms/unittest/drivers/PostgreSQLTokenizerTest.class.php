<?php namespace rdbms\unittest\drivers;

use unittest\Assert;
use unittest\Test;
 
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

  #[Test]
  public function labelToken() {
    Assert::equals(
      'select * from "order"',
      $this->fixture->prepare('select * from %l', 'order')
    );
  }
}