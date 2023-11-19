<?php namespace rdbms\unittest\integration;

use rdbms\SQLStatementFailedException;
use test\{Assert, Before, Expect, Test};
use util\{Bytes, Date};

/**
 * Sybase integration test
 *
 * @see  http://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.help.ase_15.0.blocks/html/blocks/blocks258.htm
 * @see  http://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.dc00729_1500/html/errMessageAdvRes/BABIDFFD.htm
 * @see  https://github.com/xp-framework/xp-framework/issues/274
 * @ext  sybase_ct
 */
class SybaseIntegrationTest extends RdbmsIntegrationTest {
  protected static $DRIVER= 'sybase';

  /**
   * Before class method: set minimun server severity;
   * otherwise server messages end up on the error stack
   * and will let the test fail (no error policy).
   *
   * @return void
   */
  #[Before]
  public function setMinimumServerSeverity() {
    if (function_exists('sybase_min_server_severity')) {
      sybase_min_server_severity(12);
    }
  }

  /** @return string */
  protected function tableName() { return '#unittest'; }
  
  /**
   * Create autoincrement table
   *
   * @param  rdbms.DBConnection $conn
   * @param  string $name
   * @return void
   */
  protected function createAutoIncrementTable($conn, $name) {
    $this->removeTable($conn, $name);
    $conn->query('create table %c (pk int identity, username varchar(30))', $name);
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
    $conn->query('create table %c (pk int, username varchar(30))', $name);
  }

  #[Test]
  public function selectEmptyString() {
    Assert::equals(' ', $this->db()->query('select "" as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarchar() {
    Assert::equals(' ', $this->db()->query('select cast("" as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyText() {
    Assert::equals(' ', $this->db()->query('select cast("" as text) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyImage() {
    Assert::equals(' ', $this->db()->query('select cast("" as image) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyBinary() {
    Assert::equals(' ', $this->db()->query('select cast("" as binary) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarBinary() {
    Assert::equals(' ', $this->db()->query('select cast("" as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyUniVarChar() {
    Assert::equals(' ', $this->db()->query('select cast("" as univarchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectUniVarChar() {
    Assert::equals('test', $this->db()->query('select cast("test" as univarchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautUniVarChar() {
    Assert::equals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as univarchar(255)) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullUniVarChar() {
    Assert::equals(null, $this->db()->query('select cast(NULL as univarchar(255)) as value')->next('value'));
  }

  private function runOn($version, $callable) {
    $conn= $this->db();
    if ($conn->query('select @@version_number as v')->next('v') >= $version) {
      $callable($conn);
    }
  }

  #[Test]
  public function selectEmptyUniText() {
    $this->runOn(15000, function($conn) {
      Assert::equals(' ', $conn->query('select cast("" as unitext) as value')->next('value'));
    });
  }

  #[Test]
  public function selectUniText() {
    $this->runOn(15000, function($conn) {
      Assert::equals('test', $this->db()->query('select cast("test" as unitext) as value')->next('value'));
    });
  }

  #[Test]
  public function selectUmlautUniText() {
    $this->runOn(15000, function($conn) {
      Assert::equals(
        new Bytes("\303\234bercoder"),
        new Bytes($this->db()->query('select cast("Übercoder" as unitext) as value')->next('value'))
      );
    });
  }

  #[Test]
  public function selectNullUniText() {
    $this->runOn(15000, function($conn) {
      Assert::equals(null, $this->db()->query('select cast(NULL as unitext) as value')->next('value'));
    });
  }

  #[Test]
  public function selectUnsignedInt() {
    $this->runOn(15000, function($conn) {
      parent::selectUnsignedInt();
    });
  }

  #[Test]
  public function selectMaxUnsignedBigInt() {
    $this->runOn(15000, function($conn) {
      parent::selectMaxUnsignedBigInt();
    });
  }

  #[Test, Expect(class: SQLStatementFailedException::class, message: '/More power/')]
  public function raiseError() {
    $this->db()->query('raiserror 61000 "More power"');
  }

  #[Test]
  public function printMessage() {
    $q= $this->db()->query('
      print "More power"
      select 1 as "result"
    ');
    Assert::equals(1, $q->next('result'));
  }

  #[Test, Expect(SQLStatementFailedException::class)]
  public function dataTruncationWarning() {
    $conn= $this->db();
    $conn->query('
      create table %c (
        id int primary key not null,
        cost numeric(10,4) not null
      )',
      $this->tableName()
    );
    $conn->insert('into %c (id, cost) values (1, 123.12345)', $this->tableName());
  }

  #[Test]
  public function repeated_extend_errors() {
    $conn= $this->db();
    $this->createTable($conn);
    try {
      $conn->select('not_the_table_name.field1, not_the_table_name.field2 from %c', $this->tableName());
      $this->fail('No exception raised', NULL, 'rdbms.SQLStatementFailedException');
    } catch (SQLStatementFailedException $expected) {
      // OK
    }
    Assert::equals([0 => ['working' => 1]], $conn->select('1 as working'));
  }

  #[Test]
  public function sp_helpconstraint() {
    Assert::true($this->db()->query('sp_helpconstraint %c', $this->tableName())->isSuccess());
  }

  #[Test]
  public function sp_helpconstraint_and_query() {
    $q= $this->db()->query('
      sp_helpconstraint %c
      select 1 as "result"',
      $this->tableName()
    );
    Assert::equals(1, $q->next('result'));
  }

  #[Test]
  public function longcharImplementationRegression() {
    Assert::equals([
      'field1' => 'foo',
      'field2' => 'bar'
    ], $this->db()->query('
      select
        convert(varchar(5000), "foo") as "field1",
        "bar" as "field2"
    ')->next());
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