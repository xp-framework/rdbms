<?php namespace rdbms\unittest\integration;

use rdbms\ResultSet;
use util\Date;

/**
 * SQLite integration test
 *
 * @ext       sqlite
 */
class SQLiteIntegrationTest extends RdbmsIntegrationTest {

  /** @return string */
  protected function driverName() { return 'sqlite'; }

  /**
   * Create autoincrement table
   *
   * @param   string name
   */
  protected function createAutoIncrementTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk integer primary key, username varchar(30))', $name);
  }

  /**
   * Create transactions table
   *
   * @param   string name
   */
  protected function createTransactionsTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk int, username varchar(30))', $name);
  }

  #[@test, @ignore('SQLite does not use credentials')]
  public function connectFailedThrowsException() {
    // Intentionally empty
  }

  #[@test, @ignore('Somehow AI does not work')]
  public function identity() {
    // Intentionally empty
  }

  #[@test]
  public function simpleSelect() {
    $this->assertEquals(
      [['foo' => 1]], 
      $this->db()->select('1 as foo')
    );
  }
  
  #[@test]
  public function simpleQuery() {
    $q= $this->db()->query('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(1, $q->next('foo'));
  }

  #[@test]
  public function selectInteger() {
    $this->assertEquals(1, $this->db()->query('select 1 as value')->next('value'));
  }

  #[@test]
  public function selectFloat() {
    $this->assertEquals(0.5, $this->db()->query('select 0.5 as value')->next('value'));
    $this->assertEquals(1.0, $this->db()->query('select 1.0 as value')->next('value'));
  }

  #[@test]
  public function selectDate() {
    $cmp= new Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select marshal(datetime(%s), "date") as value', $cmp)->next('value');

    $this->assertInstanceOf(Date::class, $result);
    $this->assertEquals($cmp->toString('Y-m-d'), $result->toString('Y-m-d'));
  }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNumericNull() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNumeric() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNumericZero() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNegativeNumeric() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNumericWithScaleNull() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNumericWithScale() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNumericWithScaleZero() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function selectNegativeNumericWithScale() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function select64BitLongMaxPlus1Numeric() { }

  #[@test, @ignore('SQLite does not have numeric(X)')]
  public function select64BitLongMinMinus1Numeric() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectDecimalNull() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectDecimal() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectDecimalZero() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectNegativeDecimal() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectDecimalWithScaleNull() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectDecimalWithScale() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectDecimalWithScaleZero() { }

  #[@test, @ignore('SQLite does not have decimal(X)')]
  public function selectNegativeDecimalWithScale() { }

  #[@test]
  public function selectEmptyChar() {
    $this->assertEquals('', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[@test]
  public function selectCharWithPadding() {
    $this->assertEquals('t', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[@test, @ignore('SQLite does not have an image datatype')]
  public function selectEmptyImage() { }

  #[@test, @ignore('SQLite does not have an image datatype')]
  public function selectImage() { }

  #[@test, @ignore('SQLite does not have an image datatype')]
  public function selectUmlautImage() { }

  #[@test, @ignore('SQLite does not have an varbinary datatype')]
  public function selectNullImage() { }

  #[@test, @ignore('SQLite does not have an binary datatype')]
  public function selectEmptyBinary() { }

  #[@test, @ignore('SQLite does not have an binary datatype')]
  public function selectBinary() { }

  #[@test, @ignore('SQLite does not have an binary datatype')]
  public function selectUmlautBinary() { }

  #[@test, @ignore('SQLite does not have an varbinary datatype')]
  public function selectNullBinary() { }

  #[@test, @ignore('SQLite does not have an varbinary datatype')]
  public function selectEmptyVarBinary() { }

  #[@test, @ignore('SQLite does not have an varbinary datatype')]
  public function selectVarBinary() { }

  #[@test, @ignore('SQLite does not have an varbinary datatype')]
  public function selectUmlautVarBinary() { }

  #[@test, @ignore('SQLite does not have an varbinary datatype')]
  public function selectNullVarBinary() { }

  #[@test, @ignore('SQLite does not have an money datatype')]
  public function selectMoney() { }

  #[@test, @ignore('SQLite does not have an money datatype')]
  public function selectHugeMoney() { }

  #[@test, @ignore('SQLite does not have an money datatype')]
  public function selectMoneyOne() { }

  #[@test, @ignore('SQLite does not have an money datatype')]
  public function selectMoneyZero() { }

  #[@test, @ignore('SQLite does not have an money datatype')]
  public function selectNegativeMoney() { }

  #[@test, @ignore('SQLite handles bigints differently')]
  public function selectMaxUnsignedBigInt() { }

  #[@test, @ignore('SQLite handles bigints differently')]
  public function arithmeticOverflowWithQuery() { }

  #[@test, @ignore('SQLite handles bigints differently')]
  public function arithmeticOverflowWithOpen() { }

  #[@test, @ignore('SQLite does not know about row reading failures')]
  public function readingRowFailsWithQuery() { }

  #[@test, @ignore('SQLite does not know about row reading failures')]
  public function readingRowFailsWithOpen() { }
}