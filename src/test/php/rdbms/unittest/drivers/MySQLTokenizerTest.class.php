<?php namespace rdbms\unittest\drivers;

use unittest\Assert;
use unittest\Test;
 
/**
 * Test tokenizers for MySQL based connections
 *
 * @see   xp://net.xp_framework.unittest.rdbms.TokenizerTest
 */
abstract class MySQLTokenizerTest extends \rdbms\unittest\TokenizerTest {

  #[Test]
  public function labelToken() {
    Assert::equals(
      'select * from `order`',
      $this->fixture->prepare('select * from %l', 'order')
    );
  }

  #[Test]
  public function backslash() {
    Assert::equals(
      'select \'Hello \\\\ \' as strval',
      $this->fixture->prepare('select %s as strval', 'Hello \\ ')
    );
  }
}