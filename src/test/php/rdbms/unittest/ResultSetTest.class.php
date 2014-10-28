<?php namespace rdbms\unittest;

use lang\ClassLoader;

/**
 * Tests for the abstract ResultSet base class
 *
 * @see  xp://rdbms.ResultSet
 */
class ResultSetTest extends \unittest\TestCase {
  protected static $resultSet;

  /**
   * Defines ResultSetTest_Fixture class
   *
   * @return void
   */
  #[@beforeClass]
  public static function defineResultSet() {
    self::$resultSet= ClassLoader::defineClass('ResultSetTest_Fixture', 'rdbms.ResultSet', [], '{
      protected $records;

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

  #[@test]
  public function next_on_empty_results() {
    $q= self::$resultSet->newInstance([]);
    $this->assertEquals(false, $q->next());
  }

  #[@test]
  public function next() {
    $q= self::$resultSet->newInstance([['id' => 1]]);
    $this->assertEquals(['id' => 1], $q->next());
  }

  #[@test]
  public function next_with_field() {
    $q= self::$resultSet->newInstance([['id' => 1]]);
    $this->assertEquals(1, $q->next('id'));
  }

  #[@test]
  public function seek_to_beginning() {
    $q= self::$resultSet->newInstance([['id' => 1]]);
    $q->next();
    $q->seek(0);
    $this->assertEquals(['id' => 1], $q->next());
  }

  #[@test, @values('fixtures')]
  public function can_be_used_in_foreach($records) {
    $q= self::$resultSet->newInstance($records);
    $this->assertEquals($records, iterator_to_array($q));
  }

  #[@test, @values('fixtures')]
  public function can_be_iterated_twice($records) {
    $q= self::$resultSet->newInstance($records);
    iterator_to_array($q);
    $this->assertEquals($records, iterator_to_array($q));
  }
}