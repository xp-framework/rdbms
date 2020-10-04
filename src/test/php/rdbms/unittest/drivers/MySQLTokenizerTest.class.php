<?php namespace rdbms\unittest\drivers;

use unittest\Test;
 
/**
 * Test tokenizers for MySQL based connections
 *
 * @see   xp://net.xp_framework.unittest.rdbms.TokenizerTest
 */
abstract class MySQLTokenizerTest extends \rdbms\unittest\TokenizerTest {

  #[Test]
  public function labelToken() {
    $this->assertEquals(
      'select * from `order`',
      $this->fixture->prepare('select * from %l', 'order')
    );
  }

  #[Test]
  public function backslash() {
    $this->assertEquals(
      'select \'Hello \\\\ \' as strval',
      $this->fixture->prepare('select %s as strval', 'Hello \\ ')
    );
  }
}