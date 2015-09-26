<?php namespace rdbms\unittest\drivers;
 
use util\Date;

/**
 * Test tokenizers for TDS based connections
 *
 * @see   xp://net.xp_framework.unittest.rdbms.TokenizerTest
 */
abstract class TDSTokenizerTest extends \rdbms\unittest\TokenizerTest {

  #[@test]
  public function dateToken() {
    $t= new Date('1977-12-14');
    $this->assertEquals(
      "select * from news where date= '1977-12-14 12:00:00AM'",
      $this->fixture->prepare('select * from news where date= %s', $t)
    );
  }

  #[@test]
  public function timeStampToken() {
    $t= (new Date('1977-12-14'))->getTime();
    $this->assertEquals(
      "select * from news where created= '1977-12-14 12:00:00AM'",
      $this->fixture->prepare('select * from news where created= %u', $t)
    );
  }

  #[@test]
  public function dateArrayToken() {
    $d1= new Date('1977-12-14');
    $d2= new Date('1977-12-15');
    $this->assertEquals(
      "select * from news where created in ('1977-12-14 12:00:00AM', '1977-12-15 12:00:00AM')",
      $this->fixture->prepare('select * from news where created in (%s)', [$d1, $d2])
    );
  }
}
