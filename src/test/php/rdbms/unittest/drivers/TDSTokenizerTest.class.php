<?php namespace rdbms\unittest\drivers;

use rdbms\unittest\TokenizerTest;
use test\{Assert, Test};
use util\Date;

abstract class TDSTokenizerTest extends TokenizerTest {

  #[Test]
  public function dateToken() {
    $t= new Date('1977-12-14');
    Assert::equals(
      "select * from news where date= '1977-12-14 12:00:00AM'",
      $this->fixture->prepare('select * from news where date= %s', $t)
    );
  }

  #[Test]
  public function timeStampToken() {
    $t= (new Date('1977-12-14'))->getTime();
    Assert::equals(
      "select * from news where created= '1977-12-14 12:00:00AM'",
      $this->fixture->prepare('select * from news where created= %u', $t)
    );
  }

  #[Test]
  public function dateArrayToken() {
    $d1= new Date('1977-12-14');
    $d2= new Date('1977-12-15');
    Assert::equals(
      "select * from news where created in ('1977-12-14 12:00:00AM', '1977-12-15 12:00:00AM')",
      $this->fixture->prepare('select * from news where created in (%s)', [$d1, $d2])
    );
  }
}