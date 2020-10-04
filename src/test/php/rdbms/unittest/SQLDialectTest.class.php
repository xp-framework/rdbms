<?php namespace rdbms\unittest;

use lang\IllegalArgumentException;
use rdbms\criterion\Restrictions;
use rdbms\join\{JoinRelation, JoinTable};
use rdbms\mysql\MySQLConnection;
use rdbms\sybase\SybaseConnection;
use rdbms\{SQLDialect, SQLFunction};
use unittest\{Expect, Test, TestCase, Values};
use util\Date;

/**
 * TestCase
 *
 * @see  xp://rdbms.SQLDialect
 */
class SQLDialectTest extends TestCase {
  const SYBASE= 'sybase';
  const MYSQL= 'mysql';

  protected $conn= [];
    
  /**
   * Fill conn and dialectClass members
   */
  public function __construct($name) {
    parent::__construct($name);
    $this->conn[self::MYSQL]= new MySQLConnection(new \rdbms\DSN('mysql://localhost:3306/'));
    $this->conn[self::SYBASE]= new SybaseConnection(new \rdbms\DSN('sybase://localhost:1999/'));
  }

  /**
   * Provides values for next tests
   *
   * @return  var[]
   */
  public function dialects() {
    $r= [];
    foreach ($this->conn as $name => $conn) {
      $r[]= [$conn->getFormatter()->dialect, $name];
    }
    return $r;
  }

  #[Test, Values('dialects')]
  public function dialect_member($dialect) {
    $this->assertInstanceOf(SQLDialect::class, $dialect);
  }

  #[Test, Values('dialects')]
  public function pi_function($dialect) {
    $this->assertEquals('pi()', $dialect->formatFunction(new SQLFunction('pi', '%s')));
  }

  #[Test, Values('dialects'), Expect(IllegalArgumentException::class)]
  public function unknown_function($dialect) {
    $dialect->formatFunction(new SQLFunction('foo', '%s', [1, 2, 3, 4, 5]));
  }

  #[Test, Values('dialects')]
  public function month_datepart($dialect) {
    $this->assertEquals('month', $dialect->datepart('month'));
  }

  #[Test, Values('dialects'), Expect(IllegalArgumentException::class)]
  public function unknown_datepart($dialect) {
    $dialect->datepart('month_foo_bar_buz');
  }

  #[Test, Values('dialects')]
  public function int_datatype($dialect) {
    $this->assertEquals('int', $dialect->datatype('int'));
  }

  #[Test, Values('dialects'), Expect(IllegalArgumentException::class)]
  public function unknown_datatype($dialect) {
    $dialect->datatype('int_foo_bar_buz');
  }

  #[Test, Values('dialects'), Expect(IllegalArgumentException::class)]
  public function join_by_empty($dialect) {
    $dialect->makeJoinBy([]);
  }

  #[Test, Values('dialects')]
  public function join_two_tables($dialect, $name) {
    static $asserts= [
      self::MYSQL  => 'table0 as t0 LEFT OUTER JOIN table1 as t1 on (t0.id1_1 = t0.id1_1 and t0.id1_2 = t0.id1_2) where ',
      self::SYBASE => 'table0 as t0, table1 as t1 where t0.id1_1 *= t0.id1_1 and t0.id1_2 *= t0.id1_2 and ',
    ];

    $t0= new JoinTable('table0', 't0');
    $t1= new JoinTable('table1', 't1');

    $this->assertEquals($asserts[$name], $dialect->makeJoinBy([
      new JoinRelation($t0, $t1, ['t0.id1_1 = t0.id1_1', 't0.id1_2 = t0.id1_2'])
    ]));
  }

  #[Test, Values('dialects')]
  public function join_three_tables($dialect, $name) {
    static $asserts= [
      self::MYSQL  => 'table0 as t0 LEFT OUTER JOIN table1 as t1 on (t0.id1_1 = t0.id1_1 and t0.id1_2 = t0.id1_2) LEFT JOIN table2 as t2 on (t1.id2_1 = t2.id2_1) where ',
      self::SYBASE => 'table0 as t0, table1 as t1, table2 as t2 where t0.id1_1 *= t0.id1_1 and t0.id1_2 *= t0.id1_2 and t1.id2_1 *= t2.id2_1 and ',
    ];

    $t0= new JoinTable('table0', 't0');
    $t1= new JoinTable('table1', 't1');
    $t2= new JoinTable('table2', 't2');

    $this->assertEquals($asserts[$name], $dialect->makeJoinBy([
      new JoinRelation($t0, $t1, ['t0.id1_1 = t0.id1_1', 't0.id1_2 = t0.id1_2']),
      new JoinRelation($t1, $t2, ['t1.id2_1 = t2.id2_1'])
    ]));
  }
}