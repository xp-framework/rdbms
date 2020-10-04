<?php namespace rdbms\unittest\integration;

use rdbms\SQLException;
use unittest\{Ignore, Test};

class PostgreSQLIntegrationTest extends RdbmsIntegrationTest {
  
  /** @return string */
  protected function driverName() { return 'pgsql'; }
  
  /**
   * Create autoincrement table
   *
   * @param  string $name
   * @return void
   */
  protected function createAutoIncrementTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk serial primary key, username varchar(30))', $name);
  }

  /**
   * Create transactions table
   *
   * @param  string $name
   * @return void
   */
  protected function createTransactionsTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk serial primary key, username varchar(30))', $name);
  }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericNull() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNumeric() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericZero() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNegativeNumeric() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericWithScaleNull() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericWithScale() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericWithScaleZero() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function selectNegativeNumericWithScale() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function select64BitLongMaxPlus1Numeric() { }

  #[Test, Ignore('Numeric not supported by PostgreSQL')]
  public function select64BitLongMinMinus1Numeric() { }

  #[Test, Ignore('Decimal not supported by PostgreSQL')]
  public function selectDecimalNull() { }

  #[Test, Ignore('Decimal not supported by PostgreSQL')]
  public function selectDecimal() { }

  #[Test, Ignore('Decimal not supported by PostgreSQL')]
  public function selectDecimalZero() { }

  #[Test, Ignore('Decimal not supported by PostgreSQL')]
  public function selectNegativeDecimal() { }

  #[Test, Ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectDecimalWithScaleNull() { }

  #[Test, Ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectDecimalWithScale() { }

  #[Test, Ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectDecimalWithScaleZero() { }

  #[Test, Ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectNegativeDecimalWithScale() { }

  #[Test, Ignore('Cast to float not supported by PostgreSQL')]
  public function selectFloat() { }

  #[Test, Ignore('Cast to float not supported by PostgreSQL')]
  public function selectFloatOne() { }

  #[Test, Ignore('Cast to float not supported by PostgreSQL')]
  public function selectFloatZero() { }

  #[Test, Ignore('Cast to float not supported by PostgreSQL')]
  public function selectNegativeFloat() { }

  #[Test, Ignore('Cast to real not supported by PostgreSQL')]
  public function selectReal() { }

  #[Test, Ignore('Cast to real not supported by PostgreSQL')]
  public function selectRealOne() { }

  #[Test, Ignore('Cast to real not supported by PostgreSQL')]
  public function selectRealZero() { }

  #[Test, Ignore('Cast to real not supported by PostgreSQL')]
  public function selectNegativeReal() { }

  #[Test, Ignore('Cast to varchar not supported by PostgreSQL')]
  public function selectEmptyVarChar() { }

  #[Test, Ignore('Cast to varchar not supported by PostgreSQL')]
  public function selectVarChar() { }

  #[Test, Ignore('Cast to varchar not supported by PostgreSQL')]
  public function selectNullVarChar() { }

  #[Test, Ignore('Money not supported by PostgreSQL')]
  public function selectMoney() { }

  #[Test, Ignore('Money not supported by PostgreSQL')]
  public function selectHugeMoney() { }

  #[Test, Ignore('Money not supported by PostgreSQL')]
  public function selectMoneyOne() { }

  #[Test, Ignore('Money not supported by PostgreSQL')]
  public function selectMoneyZero() { }

  #[Test, Ignore('Money not supported by PostgreSQL')]
  public function selectNegativeMoney() { }

  #[Test, Ignore('Cast to text not supported by PostgreSQL')]
  public function selectEmptyText() { }

  #[Test, Ignore('Cast to text not supported by PostgreSQL')]
  public function selectText() { }

  #[Test, Ignore('Cast to text not supported by PostgreSQL')]
  public function selectUmlautText() { }

  #[Test, Ignore('Cast to text not supported by PostgreSQL')]
  public function selectNulltext() { }

  #[Test, Ignore('Cast to Image not supported by PostgreSQL')]
  public function selectEmptyImage() { }

  #[Test, Ignore('Cast to Image not supported by PostgreSQL')]
  public function selectImage() { }

  #[Test, Ignore('Cast to Image not supported by PostgreSQL')]
  public function selectUmlautImage() { }

  #[Test, Ignore('Cast to Image not supported by PostgreSQL')]
  public function selectNullImage() { }

  #[Test, Ignore('Cast to binary not supported by PostgreSQL')]
  public function selectEmptyBinary() { }

  #[Test, Ignore('Cast to binary not supported by PostgreSQL')]
  public function selectBinary() { }

  #[Test, Ignore('Cast to binary not supported by PostgreSQL')]
  public function selectUmlautBinary() { }

  #[Test, Ignore('Cast to binary not supported by PostgreSQL')]
  public function selectNullBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectEmptyVarBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectVarBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectUmlautVarBinary() { }

  #[Test, Ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectNullVarBinary() { }

  #[Test, Ignore('No known way to test this in PostgreSQL')]
  public function readingRowFailsWithQuery() { }

  #[Test, Ignore('No known way to test this in PostgreSQL')]
  public function readingRowFailsWithOpen() { }

  #[Test, Ignore('PostgreSQL does not know unsigned bigints')]
  public function selectMaxUnsignedBigInt() { }

  #[Test, Ignore('Cast to unsigned int not supported by PostgreSQL')]
  public function selectUnsignedInt() { }

  #[Test, Ignore('Cast to tinyint not supported by PostgreSQL')]
  public function selectTinyint() { }

  #[Test, Ignore('Cast to tinyint not supported by PostgreSQL')]
  public function selectTinyintOne() { }

  #[Test, Ignore('Cast to tinyint not supported by PostgreSQL')]
  public function selectTinyintZero() { }

  #[Test, Ignore('Cast to smallint not supported by PostgreSQL')]
  public function selectSmallint() { }

  #[Test, Ignore('Cast to smallint not supported by PostgreSQL')]
  public function selectSmallintOne() { }

  #[Test, Ignore('Cast to smallint not supported by PostgreSQL')]
  public function selectSmallintZero() { }

  #[Test] 
  public function reconnects_when_server_disconnects() { 
    $conn= $this->db();
    $before= $conn->query('select pg_backend_pid() as id')->next('id'); 

    $conn->connections->reconnect(0);
    try {
      $conn->query('select pg_terminate_backend(%d)', $before);
    } catch (SQLException $expected) {
      // errorcode 57P01: Statement failed: terminating connection due to administrator command
    } 

    $conn->connections->reconnect(1);
    $after= $conn->query('select pg_backend_pid() as id')->next('id'); 
    $this->assertNotEquals($before, $after, 'Connection IDs must be different'); 
  } 
}