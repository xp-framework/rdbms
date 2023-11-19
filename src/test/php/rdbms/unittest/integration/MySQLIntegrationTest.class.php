<?php namespace rdbms\unittest\integration;

use rdbms\{SQLConnectionClosedException, SQLException, Transaction};
use unittest\Assert;
use unittest\{Ignore, Test};

class MySQLIntegrationTest extends RdbmsIntegrationTest {

  /** @return string */
  protected function driverName() { return 'mysql'; }

  /**
   * Create autoincrement table
   *
   * @param  rdbms.DBConnection $conn
   * @param  string $name
   * @return void
   */
  protected function createAutoIncrementTable($conn, $name) {
    $this->removeTable($conn, $name);
    $conn->query('create table %c (pk int primary key auto_increment, username varchar(30))', $name);
  }
  
  /**
   * Create transactions table
   *
   * @param  rdbms.DBConnection $conn
   * @param  string $name
   * @return void
   */
  protected function createTransactionsTable($conn, $name) {
    $this->removeTable($conn, $name);
    $conn->query('create table %c (pk int, username varchar(30)) Engine=InnoDB', $name);
  }

  #[Test, Ignore('Numeric not supported by MySQL')]
  public function selectNumericNull() { }

  #[Test, Ignore('Numeric not supported by MySQL')]
  public function selectNumeric() { }

  #[Test, Ignore('Numeric not supported by MySQL')]
  public function selectNumericZero() { }

  #[Test, Ignore('Numeric not supported by MySQL')]
  public function selectNegativeNumeric() { }

  #[Test, Ignore('NumericWithScale not supported by MySQL')]
  public function selectNumericWithScaleNull() { }

  #[Test, Ignore('NumericWithScale not supported by MySQL')]
  public function selectNumericWithScale() { }

  #[Test, Ignore('NumericWithScale not supported by MySQL')]
  public function selectNumericWithScaleZero() { }

  #[Test, Ignore('NumericWithScale not supported by MySQL')]
  public function selectNegativeNumericWithScale() { }

  #[Test, Ignore('Numeric not supported by MySQL')]
  public function select64BitLongMaxPlus1Numeric() { }

  #[Test, Ignore('Numeric not supported by MySQL')]
  public function select64BitLongMinMinus1Numeric() { }

  #[Test, Ignore('Decimal not supported by MySQL')]
  public function selectDecimalNull() { }

  #[Test, Ignore('Decimal not supported by MySQL')]
  public function selectDecimal() { }

  #[Test, Ignore('Decimal not supported by MySQL')]
  public function selectDecimalZero() { }

  #[Test, Ignore('Decimal not supported by MySQL')]
  public function selectNegativeDecimal() { }

  #[Test, Ignore('DecimalWithScale not supported by MySQL')]
  public function selectDecimalWithScaleNull() { }

  #[Test, Ignore('DecimalWithScale not supported by MySQL')]
  public function selectDecimalWithScale() { }

  #[Test, Ignore('DecimalWithScale not supported by MySQL')]
  public function selectDecimalWithScaleZero() { }

  #[Test, Ignore('DecimalWithScale not supported by MySQL')]
  public function selectNegativeDecimalWithScale() { }

  #[Test, Ignore('Cast to float not supported by MySQL')]
  public function selectFloat() { }

  #[Test, Ignore('Cast to float not supported by MySQL')]
  public function selectFloatOne() { }

  #[Test, Ignore('Cast to float not supported by MySQL')]
  public function selectFloatZero() { }

  #[Test, Ignore('Cast to float not supported by MySQL')]
  public function selectNegativeFloat() { }

  #[Test, Ignore('Cast to real not supported by MySQL')]
  public function selectReal() { }

  #[Test, Ignore('Cast to real not supported by MySQL')]
  public function selectRealOne() { }

  #[Test, Ignore('Cast to real not supported by MySQL')]
  public function selectRealZero() { }

  #[Test, Ignore('Cast to real not supported by MySQL')]
  public function selectNegativeReal() { }

  #[Test, Ignore('Cast to varchar not supported by MySQL')]
  public function selectEmptyVarChar() { }

  #[Test, Ignore('Cast to varchar not supported by MySQL')]
  public function selectVarChar() { }

  #[Test, Ignore('Cast to varchar not supported by MySQL')]
  public function selectNullVarChar() { }

  #[Test, Ignore('Money not supported by MySQL')]
  public function selectMoney() { }

  #[Test, Ignore('Money not supported by MySQL')]
  public function selectHugeMoney() { }

  #[Test, Ignore('Money not supported by MySQL')]
  public function selectMoneyOne() { }

  #[Test, Ignore('Money not supported by MySQL')]
  public function selectMoneyZero() { }

  #[Test, Ignore('Money not supported by MySQL')]
  public function selectNegativeMoney() { }

  #[Test, Ignore('Cast to text not supported by MySQL')]
  public function selectEmptyText() { }

  #[Test, Ignore('Cast to text not supported by MySQL')]
  public function selectText() { }

  #[Test, Ignore('Cast to text not supported by MySQL')]
  public function selectUmlautText() { }

  #[Test, Ignore('Cast to text not supported by MySQL')]
  public function selectNulltext() { }

  #[Test, Ignore('Cast to Image not supported by MySQL')]
  public function selectEmptyImage() { }

  #[Test, Ignore('Cast to Image not supported by MySQL')]
  public function selectImage() { }

  #[Test, Ignore('Cast to Image not supported by MySQL')]
  public function selectUmlautImage() { }

  #[Test, Ignore('Cast to Image not supported by MySQL')]
  public function selectNullImage() { }

  #[Test, Ignore('Cast to binary not supported by MySQL')]
  public function selectEmptyBinary() { }

  #[Test, Ignore('Cast to binary not supported by MySQL')]
  public function selectBinary() { }

  #[Test, Ignore('Cast to binary not supported by MySQL')]
  public function selectUmlautBinary() { }

  #[Test, Ignore('Cast to binary not supported by MySQL')]
  public function selectNullBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by MySQL')]
  public function selectEmptyVarBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by MySQL')]
  public function selectVarBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by MySQL')]
  public function selectUmlautVarBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by MySQL')]
  public function selectNullVarBinary() { }

  #[Test]
  public function selectEmptyChar() {
    Assert::equals('', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectCharWithPadding() {
    Assert::equals('t', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[Test, Ignore('No known way to test this in MySQL')]
  public function readingRowFailsWithQuery() { }

  #[Test, Ignore('No known way to test this in MySQL')]
  public function readingRowFailsWithOpen() { }

  #[Test]
  public function selectSignedInt() {
    Assert::equals(1, $this->db()->query('select cast(1 as signed integer) as value')->next('value'));
  }

  #[Test, Ignore('MySQL does not know unsigned bigints')]
  public function selectMaxUnsignedBigInt() { }

  #[Test, Ignore('Cast to tinyint not supported by MySQL')]
  public function selectTinyint() { }

  #[Test, Ignore('Cast to tinyint not supported by MySQL')]
  public function selectTinyintOne() { }

  #[Test, Ignore('Cast to tinyint not supported by MySQL')]
  public function selectTinyintZero() { }

  #[Test, Ignore('Cast to smallint not supported by MySQL')]
  public function selectSmallint() { }

  #[Test, Ignore('Cast to smallint not supported by MySQL')]
  public function selectSmallintOne() { }

  #[Test, Ignore('Cast to smallint not supported by MySQL')]
  public function selectSmallintZero() { }

  #[Test, Ignore('Does not cause an exception in MySQL')]
  public function arithmeticOverflowWithQuery() { }

  #[Test, Ignore('Does not cause an exception in MySQL')]
  public function arithmeticOverflowWithOpen() { }

  #[Test]
  public function selectUtf8mb4() {
    $conn= $this->db();

    // Sending characters outside the BMP while the encoding isn't utf8mb4
    // produces a warning.
    Assert::equals('💩', $conn->query("select '💩' as poop")->next('poop'));
    Assert::null($conn->query('show warnings')->next());
  }

  #[Test]
  public function reconnects_when_server_disconnects() {
    $conn= $this->db();
    $before= $conn->query('select connection_id() as id')->next('id');

    try {
      $conn->query('kill %d', $before);
    } catch (SQLException $expected) {
      // errorcode 1927: Connection was killed (sqlstate 70100)
    }

    $after= $conn->query('select connection_id() as id')->next('id');
    Assert::notEquals($before, $after, 'Connection IDs must be different');
  }
}