<?php namespace rdbms\unittest;

use rdbms\{QuerySucceeded, SQLException};
use unittest\Assert;
use unittest\{Expect, Test, Values};

class QuerySucceededTest {

  #[Test]
  public function isSuccess_always_returns_true() {
    Assert::true((new QuerySucceeded(1))->isSuccess());
  }

  #[Test, Values([0, 1, 2])]
  public function affected($rows) {
    Assert::equals($rows, (new QuerySucceeded($rows))->affected());
  }

  #[Test, Expect(SQLException::class)]
  public function cannot_seek() {
    (new QuerySucceeded(1))->seek(1);
  }

  #[Test]
  public function next_returns_false() {
    Assert::false((new QuerySucceeded(1))->next());
  }

  #[Test]
  public function iteration_yield_empty_result() {
    Assert::equals([], iterator_to_array(new QuerySucceeded(1)));
  }
}