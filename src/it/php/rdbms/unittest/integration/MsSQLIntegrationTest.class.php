<?php namespace rdbms\unittest\integration;

use rdbms\SQLStatementFailedException;
use test\{Assert, Before, Expect, Ignore, Test};
use util\Date;

/**
 * MSSQL integration test
 *
 * @see   https://github.com/xp-framework/xp-framework/issues/278
 * @see   http://msdn.microsoft.com/en-us/library/ms187942.aspx
 * @ext   mssql
 */
class MsSQLIntegrationTest extends RdbmsIntegrationTest {
  protected static $DRIVER= 'mssql';

  /**
   * Before class method: set minimun server severity;
   * otherwise server messages end up on the error stack
   * and will let the test fail (no error policy).
   *
   * @return void
   */
  #[Before]
  public function setMinimumServerSeverity() {
    if (function_exists('mssql_min_message_severity')) {
      mssql_min_message_severity(12);
    }
  }

  /** @return  string */
  protected function tableName() { return '#unittest'; }
  
  /**
   * Create autoincrement table
   *
   * @param   string name
   */
  protected function createAutoIncrementTable($conn, $name) {
    $this->removeTable($conn, $name);
    $conn->query('create table %c (pk int identity, username varchar(30))', $name);
  }
  
  /**
   * Create transactions table
   *
   * @param   string name
   */
  protected function createTransactionsTable($conn, $name) {
    $this->removeTable($conn, $name);
    $conn->query('create table %c (pk int, username varchar(30))', $name);
  }

  #[Test]
  public function selectDate() {
    $cmp= new Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select convert(datetime, %s, 120) as value', $cmp)->next('value');

    Assert::instance(Date::class, $result);
    Assert::equals($cmp->toString('Y-m-d'), $result->toString('Y-m-d'));
  }

  #[Test]
  public function selectNVarchar() {
    Assert::equals('Test', $this->db()->query('select cast("Test" as nvarchar) as value')->next('value'));
  }

  #[Test]
  public function selectNVarchars() {
    Assert::equals(
      ['one' => 'Test1', 'two' => 'Test2'],
      $this->db()->query('select cast("Test1" as nvarchar) as one, cast("Test2" as nvarchar) as two')->next()
    );
  }

  #[Test]
  public function selectVarcharVariant() {
    Assert::equals('Test', $this->db()->query('select cast("Test" as sql_variant) as value')->next('value'));
  }

  #[Test]
  public function selectVarcharVariants() {
    Assert::equals(
      ['one' => 'Test1', 'two' => 'Test2'], 
      $this->db()->query('select cast("Test1" as sql_variant) as one, cast("Test2" as sql_variant) as two')->next()
    );
  }

  #[Test]
  public function selectIntegerVariant() {
    Assert::equals(1, $this->db()->query('select cast(1 as sql_variant) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalVariant() {
    Assert::equals(1.2, $this->db()->query('select cast(1.2 as sql_variant) as value')->next('value'));
  }

  #[Test]
  public function selectNumericVariantWithFollowingVarchar() {
    Assert::equals(
      ['n' => 10, 'v' => 'Test'],
      $this->db()->query('select cast(convert(numeric, 10) as sql_variant) as n, "Test" as v')->next()
    );
  }

  #[Test]
  public function selectMoneyVariant() {
    Assert::equals(1.23, $this->db()->query('select cast($1.23 as sql_variant) as value')->next('value'));
  }

  #[Test]
  public function selectDateVariant() {
    $cmp= new Date('2009-08-14 12:45:00');
    Assert::equals($cmp, $this->db()->query('select cast(convert(datetime, %s, 102) as sql_variant) as value', $cmp)->next('value'));
  }

  #[Test]
  public function selectUniqueIdentifierariant() {
    $cmp= '0E984725-C51C-4BF4-9960-E1C80E27ABA0';
    Assert::equals($cmp, $this->db()->query('select cast(convert(uniqueidentifier, %s) as sql_variant) as value', $cmp)->next('value'));
  }

  #[Test]
  public function selectUniqueIdentifier() {
    $cmp= '0E984725-C51C-4BF4-9960-E1C80E27ABA0';
    Assert::equals($cmp, $this->db()->query('select convert(uniqueidentifier, %s) as value', $cmp)->next('value'));
  }

  #[Test]
  public function selectNullUniqueIdentifier() {
    Assert::null($this->db()->query('select convert(uniqueidentifier, NULL) as value')->next('value'));
  }

  #[Test, Ignore('MsSQL does not know unsigned ints')]
  public function selectUnsignedInt() { }

  #[Test, Ignore('MsSQL does not know unsigned bigints')]
  public function selectMaxUnsignedBigInt() { }

  #[Test]
  public function selectEmptyString() {
    Assert::equals('', $this->db()->query('select "" as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarChar() {
    Assert::equals('', $this->db()->query('select cast("" as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyImage() {
    Assert::null($this->db()->query('select cast("" as image) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarBinary() {
    Assert::null($this->db()->query('select cast("" as varbinary) as value')->next('value'));
  }

  #[Test, Expect(['class' => SQLStatementFailedException::class, 'withMessage' => '/More power/'])]
  public function raiseError() {
    $this->db()->query('raiserror ("More power", 16, 1)');
  }

  #[Test]
  public function printMessage() {
    $q= $this->db()->query('
      print "More power"
      select 1 as "result"
    ');
    Assert::equals(1, $q->next('result'));
  }

  #[Test]
  public function datetime() {
    $cmp= new Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select cast(%s as datetime) as value', $cmp)->next('value');

    Assert::instance(Date::class, $result);
    Assert::equals($cmp->toString('Y-m-d H:i:s'), $result->toString('Y-m-d H:i:s'));
  }

  #[Test]
  public function smalldatetime() {
    $cmp= new Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select cast(%s as smalldatetime) as value', $cmp)->next('value');

    Assert::instance(Date::class, $result);
    Assert::equals($cmp->toString('Y-m-d H:i:s'), $result->toString('Y-m-d H:i:s'));
  }
}