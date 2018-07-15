<?php namespace rdbms\unittest\tds;

use rdbms\tds\TdsBufferedResultSet;
use rdbms\tds\TdsProtocol;

/**
 * TestCase
 *
 * @see   xp://rdbms.tds.TdsBufferedResultSet
 */
class TdsBufferedResultSetTest extends \unittest\TestCase {
  protected static $proto;

  #[@beforeClass]
  public static function mockProtocol() {
    $parent= class_exists('\\lang\\Object', false) ? 'lang.Object' : null;
    self::$proto= \lang\ClassLoader::defineClass('rdbms.unittest.tds.MockTdsProtocol', $parent, [], '{
      private $records= [];

      public function __construct($records) {
        $this->records= $records;
      }

      public function fetch($fields) {
        return array_shift($this->records);
      }
    }');
  }

  /**
   * Creates a new result set fixture
   *
   * @param  [:var][] $result [description]
   * @return rdbms.tds.TdsBufferedResultSet
   */
  protected function newResultSet($result) {
    $records= [];
    if ($result) {
      foreach ($result[0] as  $name => $value) {
        $fields[]= [
          'name'  => $name, 
          'type'  => is_int($value) ? TdsProtocol::T_INT4 : TdsProtocol::T_VARCHAR
        ];
      }
      foreach ($result as $hash) {
        $records[]= array_values($hash);
      }
    } else {
      $fields= [];
    }

    return new TdsBufferedResultSet(self::$proto->newInstance($records), $fields);
  }

  #[@test]
  public function can_create_with_empty() { 
    $this->newResultSet([]);
  }

  #[@test]
  public function can_create() { 
    $this->newResultSet([
      [
        'id'   => 6100,
        'name' => 'Binford'
      ]
    ]);
  }

  #[@test]
  public function next() { 
    $records= [
    ];
    $fixture= $this->newResultSet($records);
    $this->assertNull($fixture->next());
  }

  #[@test]
  public function next_once() { 
    $records= [
      [
        'id'   => 6100,
        'name' => 'Binford'
      ]
    ];
    $fixture= $this->newResultSet($records);
    $this->assertEquals($records[0], $fixture->next());
  }

  #[@test]
  public function next_twice() { 
    $records= [
      [
        'id'   => 6100,
        'name' => 'Binford Lawnmower'
      ],
      [
        'id'   => 61000,
        'name' => 'Binford Moonrocket'
      ]
    ];
    $fixture= $this->newResultSet($records);
    $this->assertEquals($records[0], $fixture->next());
    $this->assertEquals($records[1], $fixture->next());
  }

  #[@test]
  public function next_returns_null_at_end() { 
    $records= [
      [
        'id'   => 6100,
        'name' => 'Binford Lawnmower'
      ],
    ];
    $fixture= $this->newResultSet($records);
    $fixture->next();
    $this->assertNull($fixture->next());
  }

  #[@test]
  public function seek_to_0_before_start() {
    $records= [
      [
        'id'   => 6100,
        'name' => 'Binford Lawnmower'
      ]
    ];
    $fixture= $this->newResultSet($records);
    $fixture->seek(0);
    $this->assertEquals($records[0], $fixture->next());
  }

  #[@test]
  public function seek_to_0_after_start() {
    $records= [
      [
        'id'   => 6100,
        'name' => 'Binford Lawnmower'
      ]
    ];
    $fixture= $this->newResultSet($records);
    $fixture->next();
    $fixture->seek(0);
    $this->assertEquals($records[0], $fixture->next());
  }

  #[@test]
  public function seek_to_1() {
    $records= [
      [
        'id'   => 6100,
        'name' => 'Binford Lawnmower'
      ],
      [
        'id'   => 61000,
        'name' => 'Binford Moonrocket'
      ]
    ];
    $fixture= $this->newResultSet($records);
    $fixture->seek(1);
    $this->assertEquals($records[1], $fixture->next());
  }

  #[@test, @expect(class= 'rdbms.SQLException', withMessage= 'Cannot seek to offset 1, out of bounds')]
  public function seek_to_offset_exceeding_length() {
    $fixture= $this->newResultSet([])->seek(1);
  }

  #[@test, @expect(class= 'rdbms.SQLException', withMessage= 'Cannot seek to offset -1, out of bounds')]
  public function seek_to_negative_offset() {
    $fixture= $this->newResultSet([])->seek(-1);
  }

  #[@test, @expect(class= 'rdbms.SQLException', withMessage= 'Cannot seek to offset 0, out of bounds')]
  public function seek_to_zero_offset_on_empty() {
    $fixture= $this->newResultSet([])->seek(0);
  }
}
