<?php namespace rdbms\unittest;

use lang\IllegalArgumentException;
use rdbms\criterion\Restrictions;
use rdbms\join\{JoinRelation, JoinTable};
use rdbms\mysql\MySQLConnection;
use rdbms\sybase\SybaseConnection;
use rdbms\{SQLDialect, SQLFunction, DSN};
use test\{Assert, Before, Expect, Test, Values};
use util\Date;

class SQLDialectTest {
  protected $dialects;
    
  #[Before]
  public function conn() {
    $fixture= [
      'mysql'  => new MySQLConnection(new DSN('mysql://localhost:3306/')),
      'sybase' => new SybaseConnection(new DSN('sybase://localhost:1999/')),
    ];
    foreach ($fixture as $name => $conn) {
      $this->dialects[$name]= [$conn->getFormatter()->dialect, $name];
    }
  }

  /** @return iterable */
  public function dialects() {
    yield from $this->dialects;
  }

  #[Test, Values(from: 'dialects')]
  public function dialect_member($dialect) {
    Assert::instance(SQLDialect::class, $dialect);
  }

  #[Test, Values(from: 'dialects')]
  public function pi_function($dialect) {
    Assert::equals('pi()', $dialect->formatFunction(new SQLFunction('pi', '%s')));
  }

  #[Test, Values(from: 'dialects'), Expect(IllegalArgumentException::class)]
  public function unknown_function($dialect) {
    $dialect->formatFunction(new SQLFunction('foo', '%s', [1, 2, 3, 4, 5]));
  }

  #[Test, Values(from: 'dialects')]
  public function month_datepart($dialect) {
    Assert::equals('month', $dialect->datepart('month'));
  }

  #[Test, Values(from: 'dialects'), Expect(IllegalArgumentException::class)]
  public function unknown_datepart($dialect) {
    $dialect->datepart('month_foo_bar_buz');
  }

  #[Test, Values(from: 'dialects')]
  public function int_datatype($dialect) {
    Assert::equals('int', $dialect->datatype('int'));
  }

  #[Test, Values(from: 'dialects'), Expect(IllegalArgumentException::class)]
  public function unknown_datatype($dialect) {
    $dialect->datatype('int_foo_bar_buz');
  }

  #[Test, Values(from: 'dialects'), Expect(IllegalArgumentException::class)]
  public function join_by_empty($dialect) {
    $dialect->makeJoinBy([]);
  }

  #[Test]
  public function join_two_tables_mysql() {
    Assert::equals(
      'table0 as t0 LEFT OUTER JOIN table1 as t1 on (t0.id1_1 = t0.id1_1 and t0.id1_2 = t0.id1_2) where ',
      $this->dialects['mysql'][0]->makeJoinBy([new JoinRelation(
        new JoinTable('table0', 't0'),
        new JoinTable('table1', 't1'),
        ['t0.id1_1 = t0.id1_1', 't0.id1_2 = t0.id1_2']
      )])
    );
  }

  #[Test]
  public function join_two_tables_sybase() {
    Assert::equals(
      'table0 as t0, table1 as t1 where t0.id1_1 *= t0.id1_1 and t0.id1_2 *= t0.id1_2 and ',
      $this->dialects['sybase'][0]->makeJoinBy([new JoinRelation(
        new JoinTable('table0', 't0'),
        new JoinTable('table1', 't1'),
        ['t0.id1_1 = t0.id1_1', 't0.id1_2 = t0.id1_2']
      )])
    );
  }

  #[Test]
  public function join_three_tables_mysql() {
    $t0= new JoinTable('table0', 't0');
    $t1= new JoinTable('table1', 't1');
    $t2= new JoinTable('table2', 't2');

    Assert::equals(
      'table0 as t0 LEFT OUTER JOIN table1 as t1 on (t0.id1_1 = t0.id1_1 and t0.id1_2 = t0.id1_2) LEFT JOIN table2 as t2 on (t1.id2_1 = t2.id2_1) where ',
      $this->dialects['mysql'][0]->makeJoinBy([
        new JoinRelation($t0, $t1, ['t0.id1_1 = t0.id1_1', 't0.id1_2 = t0.id1_2']),
        new JoinRelation($t1, $t2, ['t1.id2_1 = t2.id2_1'])
      ])
    );
  }

  #[Test]
  public function join_three_tables_sybase() {
    $t0= new JoinTable('table0', 't0');
    $t1= new JoinTable('table1', 't1');
    $t2= new JoinTable('table2', 't2');

    Assert::equals(
      'table0 as t0, table1 as t1, table2 as t2 where t0.id1_1 *= t0.id1_1 and t0.id1_2 *= t0.id1_2 and t1.id2_1 *= t2.id2_1 and ',
      $this->dialects['sybase'][0]->makeJoinBy([
        new JoinRelation($t0, $t1, ['t0.id1_1 = t0.id1_1', 't0.id1_2 = t0.id1_2']),
        new JoinRelation($t1, $t2, ['t1.id2_1 = t2.id2_1'])
      ])
    );
  }
}