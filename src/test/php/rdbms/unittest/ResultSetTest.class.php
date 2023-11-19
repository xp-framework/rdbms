<?php namespace rdbms\unittest;

use lang\ClassLoader;
use test\{Assert, Before, Test, Values};

class ResultSetTest {
  protected static $resultSet;

  /**
   * Defines ResultSetTest_Fixture class
   *
   * @return void
   */
  #[Before]
  public function defineResultSet() {
    self::$resultSet= ClassLoader::defineClass('ResultSetTest_Fixture', 'rdbms.ResultSet', [], '{
      protected $records, $offset;

      public function __construct($records) {
        $this->records= $records;
        $this->offset= 0;
      }

      public function seek($offset) {
        $this->offset= $offset;
      }

      public function next($field= null) {
        if ($this->offset < sizeof($this->records)) {
          return $field ? $this->records[$this->offset++][$field] : $this->records[$this->offset++];
        } else {
          return false;
        }
      }
    }');
  }

  /**
   * Defines record fixtures
   *
   * @return var[][]
   */
  protected function fixtures() {
    return [
      [[]],
      [[['id' => 1]]],
      [[['id' => 1], ['id' => 2]]]
    ];
  }

  #[Test]
  public function isSuccess_always_returns_false() {
    Assert::false(self::$resultSet->newInstance([])->isSuccess());
  }

  #[Test]
  public function next_on_empty_results() {
    $q= self::$resultSet->newInstance([]);
    Assert::equals(false, $q->next());
  }

  #[Test]
  public function next() {
    $q= self::$resultSet->newInstance([['id' => 1]]);
    Assert::equals(['id' => 1], $q->next());
  }

  #[Test]
  public function next_with_field() {
    $q= self::$resultSet->newInstance([['id' => 1]]);
    Assert::equals(1, $q->next('id'));
  }

  #[Test]
  public function seek_to_beginning() {
    $q= self::$resultSet->newInstance([['id' => 1]]);
    $q->next();
    $q->seek(0);
    Assert::equals(['id' => 1], $q->next());
  }

  #[Test, Values(from: 'fixtures')]
  public function can_be_used_in_foreach($records) {
    $q= self::$resultSet->newInstance($records);
    Assert::equals($records, iterator_to_array($q));
  }

  #[Test, Values(from: 'fixtures')]
  public function can_be_iterated_twice($records) {
    $q= self::$resultSet->newInstance($records);
    iterator_to_array($q);
    Assert::equals($records, iterator_to_array($q));
  }
}