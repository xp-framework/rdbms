<?php namespace rdbms\unittest;

use rdbms\QuerySucceeded;
use rdbms\SQLException;

class QuerySucceededTest extends \unittest\TestCase {

  #[@test]
  public function isSuccess_always_returns_true() {
    $this->assertTrue((new QuerySucceeded(1))->isSuccess());
  }

  #[@test, @values([0, 1, 2])]
  public function affected($rows) {
    $this->assertEquals($rows, (new QuerySucceeded($rows))->affected());
  }

  #[@test, @expect(SQLException::class)]
  public function cannot_seek() {
    (new QuerySucceeded(1))->seek(1);
  }

  #[@test]
  public function next_returns_false() {
    $this->assertFalse((new QuerySucceeded(1))->next());
  }

  #[@test]
  public function iteration_yield_empty_result() {
    $this->assertEquals([], iterator_to_array(new QuerySucceeded(1)));
  }
}