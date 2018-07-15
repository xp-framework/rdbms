<?php namespace rdbms\unittest\integration;

use rdbms\SQLException;

/**
 * PostgreSQL integration test
 *
 * @ext       pgsql
 */
class PostgreSQLIntegrationTest extends RdbmsIntegrationTest {
  
  /** @return string */
  protected function driverName() { return 'pgsql'; }
  
  /**
   * Create autoincrement table
   *
   * @param   string name
   */
  protected function createAutoIncrementTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk serial primary key, username varchar(30))', $name);
  }

  /**
   * Create transactions table
   *
   * @param   string name
   */
  protected function createTransactionsTable($name) {
    $this->removeTable($name);
    $this->db()->query('create table %c (pk serial primary key, username varchar(30))', $name);
  }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericNull() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNumeric() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericZero() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNegativeNumeric() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericWithScaleNull() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericWithScale() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNumericWithScaleZero() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function selectNegativeNumericWithScale() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function select64BitLongMaxPlus1Numeric() { }

  #[@test, @ignore('Numeric not supported by PostgreSQL')]
  public function select64BitLongMinMinus1Numeric() { }

  #[@test, @ignore('Decimal not supported by PostgreSQL')]
  public function selectDecimalNull() { }

  #[@test, @ignore('Decimal not supported by PostgreSQL')]
  public function selectDecimal() { }

  #[@test, @ignore('Decimal not supported by PostgreSQL')]
  public function selectDecimalZero() { }

  #[@test, @ignore('Decimal not supported by PostgreSQL')]
  public function selectNegativeDecimal() { }

  #[@test, @ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectDecimalWithScaleNull() { }

  #[@test, @ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectDecimalWithScale() { }

  #[@test, @ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectDecimalWithScaleZero() { }

  #[@test, @ignore('DecimalWithScale not supported by PostgreSQL')]
  public function selectNegativeDecimalWithScale() { }

  #[@test, @ignore('Cast to float not supported by PostgreSQL')]
  public function selectFloat() { }

  #[@test, @ignore('Cast to float not supported by PostgreSQL')]
  public function selectFloatOne() { }

  #[@test, @ignore('Cast to float not supported by PostgreSQL')]
  public function selectFloatZero() { }

  #[@test, @ignore('Cast to float not supported by PostgreSQL')]
  public function selectNegativeFloat() { }

  #[@test, @ignore('Cast to real not supported by PostgreSQL')]
  public function selectReal() { }

  #[@test, @ignore('Cast to real not supported by PostgreSQL')]
  public function selectRealOne() { }

  #[@test, @ignore('Cast to real not supported by PostgreSQL')]
  public function selectRealZero() { }

  #[@test, @ignore('Cast to real not supported by PostgreSQL')]
  public function selectNegativeReal() { }

  #[@test, @ignore('Cast to varchar not supported by PostgreSQL')]
  public function selectEmptyVarChar() { }

  #[@test, @ignore('Cast to varchar not supported by PostgreSQL')]
  public function selectVarChar() { }

  #[@test, @ignore('Cast to varchar not supported by PostgreSQL')]
  public function selectNullVarChar() { }

  #[@test, @ignore('Money not supported by PostgreSQL')]
  public function selectMoney() { }

  #[@test, @ignore('Money not supported by PostgreSQL')]
  public function selectHugeMoney() { }

  #[@test, @ignore('Money not supported by PostgreSQL')]
  public function selectMoneyOne() { }

  #[@test, @ignore('Money not supported by PostgreSQL')]
  public function selectMoneyZero() { }

  #[@test, @ignore('Money not supported by PostgreSQL')]
  public function selectNegativeMoney() { }

  #[@test, @ignore('Cast to text not supported by PostgreSQL')]
  public function selectEmptyText() { }

  #[@test, @ignore('Cast to text not supported by PostgreSQL')]
  public function selectText() { }

  #[@test, @ignore('Cast to text not supported by PostgreSQL')]
  public function selectUmlautText() { }

  #[@test, @ignore('Cast to text not supported by PostgreSQL')]
  public function selectNulltext() { }

  #[@test, @ignore('Cast to Image not supported by PostgreSQL')]
  public function selectEmptyImage() { }

  #[@test, @ignore('Cast to Image not supported by PostgreSQL')]
  public function selectImage() { }

  #[@test, @ignore('Cast to Image not supported by PostgreSQL')]
  public function selectUmlautImage() { }

  #[@test, @ignore('Cast to Image not supported by PostgreSQL')]
  public function selectNullImage() { }

  #[@test, @ignore('Cast to binary not supported by PostgreSQL')]
  public function selectEmptyBinary() { }

  #[@test, @ignore('Cast to binary not supported by PostgreSQL')]
  public function selectBinary() { }

  #[@test, @ignore('Cast to binary not supported by PostgreSQL')]
  public function selectUmlautBinary() { }

  #[@test, @ignore('Cast to binary not supported by PostgreSQL')]
  public function selectNullBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectEmptyVarBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectVarBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectUmlautVarBinary() { }

  #[@test, @ignore('Cast to varbinary not supported by PostgreSQL')]
  public function selectNullVarBinary() { }

  #[@test, @ignore('No known way to test this in PostgreSQL')]
  public function readingRowFailsWithQuery() { }

  #[@test, @ignore('No known way to test this in PostgreSQL')]
  public function readingRowFailsWithOpen() { }

  #[@test, @ignore('PostgreSQL does not know unsigned bigints')]
  public function selectMaxUnsignedBigInt() { }

  #[@test, @ignore('Cast to unsigned int not supported by PostgreSQL')]
  public function selectUnsignedInt() { }

  #[@test, @ignore('Cast to tinyint not supported by PostgreSQL')]
  public function selectTinyint() { }

  #[@test, @ignore('Cast to tinyint not supported by PostgreSQL')]
  public function selectTinyintOne() { }

  #[@test, @ignore('Cast to tinyint not supported by PostgreSQL')]
  public function selectTinyintZero() { }

  #[@test, @ignore('Cast to smallint not supported by PostgreSQL')]
  public function selectSmallint() { }

  #[@test, @ignore('Cast to smallint not supported by PostgreSQL')]
  public function selectSmallintOne() { }

  #[@test, @ignore('Cast to smallint not supported by PostgreSQL')]
  public function selectSmallintZero() { }

  #[@test] 
  public function reconnects_when_server_disconnects() { 
    $conn= $this->db();
    $before= $conn->query('select pg_backend_pid() as id')->next('id'); 

    try { 
     $conn->query('select pg_terminate_backend(%d)', $before); 
    } catch (SQLException $expected) { 
     // errorcode 1927: Connection was killed (sqlstate 70100) 
    } 

    $after= $conn->query('select pg_backend_pid() as id')->next('id'); 
    $this->assertNotEquals($before, $after, 'Connection IDs must be different'); 
  } 
}
