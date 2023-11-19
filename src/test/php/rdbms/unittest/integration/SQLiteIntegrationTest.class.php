<?php namespace rdbms\unittest\integration;

use rdbms\ResultSet;
use unittest\Assert;
use unittest\{Ignore, Test};
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

  #[Test, Ignore('SQLite does not use credentials')]
  public function connectFailedThrowsException() {
    // Intentionally empty
  }

  #[Test, Ignore('Somehow AI does not work')]
  public function identity() {
    // Intentionally empty
  }

  #[Test]
  public function simpleSelect() {
    Assert::equals(
      [['foo' => 1]], 
      $this->db()->select('1 as foo')
    );
  }
  
  #[Test]
  public function simpleQuery() {
    $q= $this->db()->query('select 1 as foo');
    Assert::instance(ResultSet::class, $q);
    Assert::equals(1, $q->next('foo'));
  }

  #[Test]
  public function selectInteger() {
    Assert::equals(1, $this->db()->query('select 1 as value')->next('value'));
  }

  #[Test]
  public function selectFloat() {
    Assert::equals(0.5, $this->db()->query('select 0.5 as value')->next('value'));
    Assert::equals(1.0, $this->db()->query('select 1.0 as value')->next('value'));
  }

  #[Test]
  public function selectDate() {
    $cmp= new Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select marshal(datetime(%s), "date") as value', $cmp)->next('value');

    Assert::instance(Date::class, $result);
    Assert::equals($cmp->toString('Y-m-d'), $result->toString('Y-m-d'));
  }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNumericNull() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNumeric() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNumericZero() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNegativeNumeric() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNumericWithScaleNull() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNumericWithScale() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNumericWithScaleZero() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function selectNegativeNumericWithScale() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function select64BitLongMaxPlus1Numeric() { }

  #[Test, Ignore('SQLite does not have numeric(X)')]
  public function select64BitLongMinMinus1Numeric() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectDecimalNull() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectDecimal() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectDecimalZero() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectNegativeDecimal() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectDecimalWithScaleNull() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectDecimalWithScale() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectDecimalWithScaleZero() { }

  #[Test, Ignore('SQLite does not have decimal(X)')]
  public function selectNegativeDecimalWithScale() { }

  #[Test]
  public function selectEmptyChar() {
    Assert::equals('', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectCharWithPadding() {
    Assert::equals('t', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[Test, Ignore('SQLite does not have an image datatype')]
  public function selectEmptyImage() { }

  #[Test, Ignore('SQLite does not have an image datatype')]
  public function selectImage() { }

  #[Test, Ignore('SQLite does not have an image datatype')]
  public function selectUmlautImage() { }

  #[Test, Ignore('SQLite does not have an varbinary datatype')]
  public function selectNullImage() { }

  #[Test, Ignore('SQLite does not have an binary datatype')]
  public function selectEmptyBinary() { }

  #[Test, Ignore('SQLite does not have an binary datatype')]
  public function selectBinary() { }

  #[Test, Ignore('SQLite does not have an binary datatype')]
  public function selectUmlautBinary() { }

  #[Test, Ignore('SQLite does not have an varbinary datatype')]
  public function selectNullBinary() { }

  #[Test, Ignore('SQLite does not have an varbinary datatype')]
  public function selectEmptyVarBinary() { }

  #[Test, Ignore('SQLite does not have an varbinary datatype')]
  public function selectVarBinary() { }

  #[Test, Ignore('SQLite does not have an varbinary datatype')]
  public function selectUmlautVarBinary() { }

  #[Test, Ignore('SQLite does not have an varbinary datatype')]
  public function selectNullVarBinary() { }

  #[Test, Ignore('SQLite does not have an money datatype')]
  public function selectMoney() { }

  #[Test, Ignore('SQLite does not have an money datatype')]
  public function selectHugeMoney() { }

  #[Test, Ignore('SQLite does not have an money datatype')]
  public function selectMoneyOne() { }

  #[Test, Ignore('SQLite does not have an money datatype')]
  public function selectMoneyZero() { }

  #[Test, Ignore('SQLite does not have an money datatype')]
  public function selectNegativeMoney() { }

  #[Test, Ignore('SQLite handles bigints differently')]
  public function selectMaxUnsignedBigInt() { }

  #[Test, Ignore('SQLite handles bigints differently')]
  public function arithmeticOverflowWithQuery() { }

  #[Test, Ignore('SQLite handles bigints differently')]
  public function arithmeticOverflowWithOpen() { }

  #[Test, Ignore('SQLite does not know about row reading failures')]
  public function readingRowFailsWithQuery() { }

  #[Test, Ignore('SQLite does not know about row reading failures')]
  public function readingRowFailsWithOpen() { }
}