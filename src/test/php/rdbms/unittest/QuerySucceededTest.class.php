<?php namespace rdbms\unittest;

use rdbms\{QuerySucceeded, SQLException};
use unittest\{Expect, Test, Values};

class QuerySucceededTest extends \unittest\TestCase {

  #[Test]
  public function isSuccess_always_returns_true() {
    $this->assertTrue((new QuerySucceeded(1))->isSuccess());
  }

  #[Test, Values([0, 1, 2])]
  public function affected($rows) {
    $this->assertEquals($rows, (new QuerySucceeded($rows))->affected());
  }

  #[Test, Expect(SQLException::class)]
  public function cannot_seek() {
    (new QuerySucceeded(1))->seek(1);
  }

  #[Test]
  public function next_returns_false() {
    $this->assertFalse((new QuerySucceeded(1))->next());
  }

  #[Test]
  public function iteration_yield_empty_result() {
    $this->assertEquals([], iterator_to_array(new QuerySucceeded(1)));
  }
}