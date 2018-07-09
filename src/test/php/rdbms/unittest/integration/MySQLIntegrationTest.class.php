<?php namespace rdbms\unittest\integration;

use rdbms\PersistentConnection;
use rdbms\SQLStatementFailedException;

/**
 * MySQL integration test
 *
 * @ext       mysql
 */
class MySQLIntegrationTest extends RdbmsIntegrationTest {

  /** @return string */
  protected function driverName() { return 'mysql'; }

  /** @return void */
  public function tearDown() {
    parent::tearDown();

    // Suppress "mysql_connect(): The mysql extension is deprecated [...]"
    foreach (\xp::$errors as $file => $errors) {
      if (strstr($file, 'MySQLConnection')) {
        unset(\xp::$errors[$file]);
      }
    }
  }

  /**
   * Create autoincrement table
   *
   * @param   string name
   */
  protected function createAutoIncrementTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk int primary key auto_increment, username varchar(30))', $name);
  }
  
  /**
   * Create transactions table
   *
   * @param   string name
   */
  protected function createTransactionsTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk int, username varchar(30)) Engine=InnoDB', $name);
  }

  #[@test, @ignore('Numeric not supported by MySQL')]
  public function selectNumericNull() { }

  #[@test, @ignore('Numeric not supported by MySQL')]
  public function selectNumeric() { }

  #[@test, @ignore('Numeric not supported by MySQL')]
  public function selectNumericZero() { }

  #[@test, @ignore('Numeric not supported by MySQL')]
  public function selectNegativeNumeric() { }

  #[@test, @ignore('NumericWithScale not supported by MySQL')]
  public function selectNumericWithScaleNull() { }

  #[@test, @ignore('NumericWithScale not supported by MySQL')]
  public function selectNumericWithScale() { }

  #[@test, @ignore('NumericWithScale not supported by MySQL')]
  public function selectNumericWithScaleZero() { }

  #[@test, @ignore('NumericWithScale not supported by MySQL')]
  public function selectNegativeNumericWithScale() { }

  #[@test, @ignore('Numeric not supported by MySQL')]
  public function select64BitLongMaxPlus1Numeric() { }

  #[@test, @ignore('Numeric not supported by MySQL')]
  public function select64BitLongMinMinus1Numeric() { }

  #[@test, @ignore('Decimal not supported by MySQL')]
  public function selectDecimalNull() { }

  #[@test, @ignore('Decimal not supported by MySQL')]
  public function selectDecimal() { }

  #[@test, @ignore('Decimal not supported by MySQL')]
  public function selectDecimalZero() { }

  #[@test, @ignore('Decimal not supported by MySQL')]
  public function selectNegativeDecimal() { }

  #[@test, @ignore('DecimalWithScale not supported by MySQL')]
  public function selectDecimalWithScaleNull() { }

  #[@test, @ignore('DecimalWithScale not supported by MySQL')]
  public function selectDecimalWithScale() { }

  #[@test, @ignore('DecimalWithScale not supported by MySQL')]
  public function selectDecimalWithScaleZero() { }

  #[@test, @ignore('DecimalWithScale not supported by MySQL')]
  public function selectNegativeDecimalWithScale() { }

  #[@test, @ignore('Cast to float not supported by MySQL')]
  public function selectFloat() { }

  #[@test, @ignore('Cast to float not supported by MySQL')]
  public function selectFloatOne() { }

  #[@test, @ignore('Cast to float not supported by MySQL')]
  public function selectFloatZero() { }

  #[@test, @ignore('Cast to float not supported by MySQL')]
  public function selectNegativeFloat() { }

  #[@test, @ignore('Cast to real not supported by MySQL')]
  public function selectReal() { }

  #[@test, @ignore('Cast to real not supported by MySQL')]
  public function selectRealOne() { }

  #[@test, @ignore('Cast to real not supported by MySQL')]
  public function selectRealZero() { }

  #[@test, @ignore('Cast to real not supported by MySQL')]
  public function selectNegativeReal() { }

  #[@test, @ignore('Cast to varchar not supported by MySQL')]
  public function selectEmptyVarChar() { }

  #[@test, @ignore('Cast to varchar not supported by MySQL')]
  public function selectVarChar() { }

  #[@test, @ignore('Cast to varchar not supported by MySQL')]
  public function selectNullVarChar() { }

  #[@test, @ignore('Money not supported by MySQL')]
  public function selectMoney() { }

  #[@test, @ignore('Money not supported by MySQL')]
  public function selectHugeMoney() { }

  #[@test, @ignore('Money not supported by MySQL')]
  public function selectMoneyOne() { }

  #[@test, @ignore('Money not supported by MySQL')]
  public function selectMoneyZero() { }

  #[@test, @ignore('Money not supported by MySQL')]
  public function selectNegativeMoney() { }

  #[@test, @ignore('Cast to text not supported by MySQL')]
  public function selectEmptyText() { }

  #[@test, @ignore('Cast to text not supported by MySQL')]
  public function selectText() { }

  #[@test, @ignore('Cast to text not supported by MySQL')]
  public function selectUmlautText() { }

  #[@test, @ignore('Cast to text not supported by MySQL')]
  public function selectNulltext() { }

  #[@test, @ignore('Cast to Image not supported by MySQL')]
  public function selectEmptyImage() { }

  #[@test, @ignore('Cast to Image not supported by MySQL')]
  public function selectImage() { }

  #[@test, @ignore('Cast to Image not supported by MySQL')]
  public function selectUmlautImage() { }

  #[@test, @ignore('Cast to Image not supported by MySQL')]
  public function selectNullImage() { }

  #[@test, @ignore('Cast to binary not supported by MySQL')]
  public function selectEmptyBinary() { }

  #[@test, @ignore('Cast to binary not supported by MySQL')]
  public function selectBinary() { }

  #[@test, @ignore('Cast to binary not supported by MySQL')]
  public function selectUmlautBinary() { }

  #[@test, @ignore('Cast to binary not supported by MySQL')]
  public function selectNullBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by MySQL')]
  public function selectEmptyVarBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by MySQL')]
  public function selectVarBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by MySQL')]
  public function selectUmlautVarBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by MySQL')]
  public function selectNullVarBinary() { }

  #[@test]
  public function selectEmptyChar() {
    $this->assertEquals('', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[@test]
  public function selectCharWithPadding() {
    $this->assertEquals('t', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[@test, @ignore('No known way to test this in MySQL')]
  public function readingRowFailsWithQuery() { }

  #[@test, @ignore('No known way to test this in MySQL')]
  public function readingRowFailsWithOpen() { }

  #[@test]
  public function selectSignedInt() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as signed integer) as value')->next('value'));
  }

  #[@test, @ignore('MySQL does not know unsigned bigints')]
  public function selectMaxUnsignedBigInt() { }

  #[@test, @ignore('Cast to tinyint not supported by MySQL')]
  public function selectTinyint() { }

  #[@test, @ignore('Cast to tinyint not supported by MySQL')]
  public function selectTinyintOne() { }

  #[@test, @ignore('Cast to tinyint not supported by MySQL')]
  public function selectTinyintZero() { }

  #[@test, @ignore('Cast to smallint not supported by MySQL')]
  public function selectSmallint() { }

  #[@test, @ignore('Cast to smallint not supported by MySQL')]
  public function selectSmallintOne() { }

  #[@test, @ignore('Cast to smallint not supported by MySQL')]
  public function selectSmallintZero() { }

  #[@test]
  public function selectUtf8mb4() {

    // Sending characters outside the BMP while the encoding isn't utf8mb4
    // produces a warning.
    $this->assertEquals('💩', $this->db()->query("select '💩' as poop")->next('poop'));
    $this->assertNull($this->db()->query('show warnings')->next());
  }

  #[@test]
  public function reconnects_when_server_disconnects() {
    $conn= new PersistentConnection($this->db());
    $before= $conn->query('select connection_id() as id')->next('id');

    try {
      $conn->query('kill %d', $before);
    } catch (SQLException $expected) {
      // errorcode 1927: Connection was killed (sqlstate 70100)
    }

    $after= $conn->query('select connection_id() as id')->next('id');
    $this->assertNotEquals($before, $after, 'Connection IDs must be different');
  }
}