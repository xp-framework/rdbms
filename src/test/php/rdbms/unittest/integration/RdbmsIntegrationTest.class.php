<?php namespace rdbms\unittest\integration;

use lang\{MethodNotImplementedException, Throwable};
use rdbms\{DBEvent, DSN, DriverManager, ResultSet, SQLConnectException, SQLException, SQLStateException, SQLStatementFailedException};
use unittest\Assert;
use unittest\{Expect, PrerequisitesNotMetError, Test, TestCase};
use util\{Bytes, Date, Observer};

/**
 * Base class for all RDBMS integration tests
 */
abstract class RdbmsIntegrationTest {
  private $dsn, $conn;

  /** @return void */
  #[Before]
  public function setUp() {
    $env= strtoupper($this->driverName()).'_DSN';
    if (!($this->dsn= getenv($env))) {
      throw new PrerequisitesNotMetError('No credentials for '.nameof($this).', use '.$env.' to set');
    }

    try {
      $this->conn= DriverManager::getConnection($this->dsn);
    } catch (Throwable $t) {
      throw new PrerequisitesNotMetError($t->getMessage(), $t);
    }
  }

  /** @return void */
  #[After]
  public function tearDown() {
    $this->conn && $this->conn->close();
  }

  /**
   * Creates table name. Override in subclasses if necessary!
   *
   * @return  string
   */
  protected function tableName() { return 'unittest'; }

  /**
   * Retrieve driver name
   *
   * @return  string
   */
  abstract protected function driverName();

  /**
   * Retrieve database connection object
   *
   * @param   bool $connect default TRUE
   * @return  rdbms.DBConnection
   */
  protected function db($connect= true) {
    $connect && $this->conn->connect();
    return $this->conn;
  }

  /**
   * Helper method to remove table if existant
   *
   * @param   string name
   */
  protected function removeTable($name) {
    // Try to remove, if already exist...
    try {
      $this->db()->query('drop table %c', $name);
    } catch (\rdbms\SQLStatementFailedException $ignored) {}
  }

  /**
   * Create autoincrement table
   *
   * @return void
   */
  protected function createTable() {
    $this->removeTable($this->tableName());
    $this->db()->query('create table %c (pk int, username varchar(30))', $this->tableName());
    $this->db()->insert('into %c values (1, "kiesel")', $this->tableName());
    $this->db()->insert('into %c values (2, "kiesel")', $this->tableName());
  }

  /**
   * Helper method to create table
   *
   * @param   string name
   */
  protected function createAutoIncrementTable($name) {
    throw new MethodNotImplementedException($name, __FUNCTION__);
  }

  /**
   * Create transactions table
   *
   * @param   string name
   */
  protected function createTransactionsTable($name) {
    throw new MethodNotImplementedException($name, __FUNCTION__);
  }

  /**
   * Creates fixture for readingRowFailsWithQuery* tests
   *
   * @return  string SQL
   */
  protected function rowFailureFixture() {
    $this->removeTable($this->tableName());
    $this->db()->query('create table %c (i varchar(20))', $this->tableName());
    $this->db()->insert('into %c values ("1")', $this->tableName());
    $this->db()->insert('into %c values ("not-a-number")', $this->tableName());
    $this->db()->insert('into %c values ("2")', $this->tableName());
    return $this->db()->prepare('select cast(i as int) as i from %c', $this->tableName());
  }

  #[Test, Expect(SQLStateException::class)]
  public function noQueryWhenNotConnected() {
    $this->conn->connections->automatic(false);
    $this->conn->query('select 1');
  }
  
  #[Test, Expect(SQLConnectException::class)]
  public function connectFailedThrowsException() {
    $dsn= new DSN($this->dsn);
    $dsn->url->setUser('wrong-user');
    $dsn->url->setPassword('wrong-password');

    DriverManager::getConnection($dsn)->connect();
  }
  
  #[Test]
  public function connect() {
    Assert::equals(true, $this->conn->connect());
  }

  #[Test, Expect(SQLStateException::class)]
  public function noQueryWhenDisConnected() {
    $this->conn->connections->automatic(false);
    $this->conn->connect();
    $this->conn->close();
    $this->conn->query('select 1');
  }
  
  #[Test]
  public function simpleSelect() {
    Assert::equals(
      [['foo' => 1]], 
      $this->db()->select('1 as foo')
    );
  }

  #[Test]
  public function queryAndNext() {
    $q= $this->db()->query('select 1 as foo');
    Assert::instance(ResultSet::class, $q);
    Assert::equals(['foo' => 1], $q->next());
  }
 
  #[Test]
  public function queryAndNextWithField() {
    $q= $this->db()->query('select 1 as foo');
    Assert::instance(ResultSet::class, $q);
    Assert::equals(1, $q->next('foo'));
  }

  #[Test]
  public function openAndNext() {
    $q= $this->db()->open('select 1 as foo');
    Assert::instance(ResultSet::class, $q);
    Assert::equals(['foo' => 1], $q->next());
  }

  #[Test]
  public function openAndNextWithField() {
    $q= $this->db()->open('select 1 as foo');
    Assert::instance(ResultSet::class, $q);
    Assert::equals(1, $q->next('foo'));
  }
 
  #[Test]
  public function emptyQuery() {
    $this->createTable();
    $q= $this->db()->query('select * from %c where 1 = 0', $this->tableName());
    Assert::instance(ResultSet::class, $q);
    Assert::null($q->next());
  }

  #[Test]
  public function insertViaQuery() {
    $this->createTable();
    Assert::true($this->db()->query('insert into %c values (1, "kiesel")', $this->tableName())->isSuccess());
  }

  #[Test]
  public function insertIntoTable() {
    $this->createTable();
    Assert::equals(1, $this->db()->insert('into %c values (2, "xp")', $this->tableName()));
  }

  #[Test]
  public function updateViaQuery() {
    $this->createTable();
    Assert::true($this->db()->query('update %c set pk= pk+ 1 where pk= 2', $this->tableName())->isSuccess());
  }
  
  #[Test]
  public function updateTable() {
    $this->createTable();
    Assert::equals(1, $this->db()->update('%c set pk= pk+ 1 where pk= 1', $this->tableName()));
  }

  #[Test]
  public function deleteViaQuery() {
    $this->createTable();
    Assert::true($this->db()->query('delete from %c where pk= 2', $this->tableName())->isSuccess());
  }
  
  #[Test]
  public function deleteFromTable() {
    $this->createTable();
    Assert::equals(1, $this->db()->delete('from %c where pk= 1', $this->tableName()));
  }
  
  #[Test]
  public function identity() {
    $this->createAutoIncrementTable($this->tableName());      
    Assert::equals(1, $this->db()->insert('into %c (username) values ("kiesel")', $this->tableName()));
    $first= $this->db()->identity('unittest_pk_seq');
    
    Assert::equals(1, $this->db()->insert('into %c (username) values ("kiesel")', $this->tableName()));
    Assert::equals($first+ 1, $this->db()->identity('unittest_pk_seq'));
  }
  
  #[Test, Expect(SQLStatementFailedException::class)]
  public function malformedStatement() {
    $this->db()->query('select insert into delete.');
  }

  #[Test]
  public function selectNull() {
    Assert::equals(null, $this->db()->query('select NULL as value')->next('value'));
  }
  
  #[Test]
  public function selectInteger() {
    Assert::equals(1, $this->db()->query('select 1 as value')->next('value'));
  }

  #[Test]
  public function selectIntegerZero() {
    Assert::equals(0, $this->db()->query('select 0 as value')->next('value'));
  }

  #[Test]
  public function selectNegativeInteger() {
    Assert::equals(-6100, $this->db()->query('select -6100 as value')->next('value'));
  }

  #[Test]
  public function selectString() {
    Assert::equals('Hello, World!', $this->db()->query('select "Hello, World!" as value')->next('value'));
  }

  #[Test]
  public function selectEmptyString() {
    Assert::equals('', $this->db()->query('select "" as value')->next('value'));
  }

  #[Test]
  public function selectSpace() {
    Assert::equals(' ', $this->db()->query('select " " as value')->next('value'));
  }

  #[Test]
  public function selectUmlautString() {
    Assert::equals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select %s as value', 'Übercoder')->next('value'))
    );
  }
  
  #[Test]
  public function selectDecimalLiteral() {
    Assert::equals(0.5, $this->db()->query('select 0.5 as value')->next('value'));
  }

  #[Test]
  public function selectDecimalLiteralOne() {
    Assert::equals(1.0, $this->db()->query('select 1.0 as value')->next('value'));
  }

  #[Test]
  public function selectDecimalLiteralZero() {
    Assert::equals(0.0, $this->db()->query('select 0.0 as value')->next('value'));
  }

  #[Test]
  public function selectNegativeDecimalLiteral() {
    Assert::equals(-6.1, $this->db()->query('select -6.1 as value')->next('value'));
  }
  
  #[Test]
  public function selectFloat() {
    Assert::equals(0.5, $this->db()->query('select cast(0.5 as float) as value')->next('value'));
  }

  #[Test]
  public function selectFloatOne() {
    Assert::equals(1.0, $this->db()->query('select cast(1.0 as float) as value')->next('value'));
  }

  #[Test]
  public function selectFloatZero() {
    Assert::equals(0.0, $this->db()->query('select cast(0.0 as float) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeFloat() {
    Assert::equals(-6.1, round($this->db()->query('select cast(-6.1 as float) as value')->next('value'), 1));
  }

  #[Test]
  public function selectReal() {
    Assert::equals(0.5, $this->db()->query('select cast(0.5 as real) as value')->next('value'));
  }

  #[Test]
  public function selectRealOne() {
    Assert::equals(1.0, $this->db()->query('select cast(1.0 as real) as value')->next('value'));
  }

  #[Test]
  public function selectRealZero() {
    Assert::equals(0.0, $this->db()->query('select cast(0.0 as real) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeReal() {
    Assert::equals(-6.1, round($this->db()->query('select cast(-6.1 as real) as value')->next('value'), 1));
  }
  
  #[Test]
  public function selectDate() {
    $cmp= new \util\Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select cast(%s as date) as value', $cmp)->next('value');
    
    Assert::instance(Date::class, $result);
    Assert::equals($cmp->toString('Y-m-d'), $result->toString('Y-m-d'));
  }

  #[Test]
  public function selectNumericNull() {
    Assert::equals(null, $this->db()->query('select convert(numeric(8), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectNumeric() {
    Assert::equals(1, $this->db()->query('select convert(numeric(8), 1) as value')->next('value'));
  }

  #[Test]
  public function selectNumericZero() {
    Assert::equals(0, $this->db()->query('select convert(numeric(8), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeNumeric() {
    Assert::equals(-6100, $this->db()->query('select convert(numeric(8), -6100) as value')->next('value'));
  }

  #[Test]
  public function selectNumericWithScaleNull() {
    Assert::equals(null, $this->db()->query('select convert(numeric(8, 2), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectNumericWithScale() {
    Assert::equals(1.00, $this->db()->query('select convert(numeric(8, 2), 1) as value')->next('value'));
  }

  #[Test]
  public function selectNumericWithScaleZero() {
    Assert::equals(0.00, $this->db()->query('select convert(numeric(8, 2), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeNumericWithScale() {
    Assert::equals(-6100.00, $this->db()->query('select convert(numeric(8, 2), -6100) as value')->next('value'));
  }

  #[Test]
  public function select64BitLongMaxPlus1Numeric() {
    Assert::equals('9223372036854775808', $this->db()->query('select convert(numeric(20), 9223372036854775808) as value')->next('value'));
  }

  #[Test]
  public function select64BitLongMinMinus1Numeric() {
    Assert::equals('-9223372036854775809', $this->db()->query('select convert(numeric(20), -9223372036854775809) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalNull() {
    Assert::equals(null, $this->db()->query('select convert(decimal(8), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectDecimal() {
    Assert::equals(1, $this->db()->query('select convert(decimal(8), 1) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalZero() {
    Assert::equals(0, $this->db()->query('select convert(decimal(8), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeDecimal() {
    Assert::equals(-6100, $this->db()->query('select convert(decimal(8), -6100) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalWithScaleNull() {
    Assert::equals(null, $this->db()->query('select convert(decimal(8, 2), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalWithScale() {
    Assert::equals(1.00, $this->db()->query('select convert(decimal(8, 2), 1) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalWithScaleZero() {
    Assert::equals(0.00, $this->db()->query('select convert(decimal(8, 2), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeDecimalWithScale() {
    Assert::equals(-6100.00, $this->db()->query('select convert(decimal(8, 2), -6100) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyChar() {
    Assert::equals('    ', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectCharWithoutPadding() {
    Assert::equals('test', $this->db()->query('select cast("test" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectCharWithPadding() {
    Assert::equals('t   ', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarChar() {
    Assert::equals('', $this->db()->query('select cast("" as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectVarChar() {
    Assert::equals('test', $this->db()->query('select cast("test" as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectNullVarChar() {
    Assert::equals(null, $this->db()->query('select cast(NULL as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyText() {
    Assert::equals('', $this->db()->query('select cast("" as text) as value')->next('value'));
  }

  #[Test]
  public function selectText() {
    Assert::equals('test', $this->db()->query('select cast("test" as text) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautText() {
    Assert::equals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as text) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNulltext() {
    Assert::equals(null, $this->db()->query('select cast(NULL as text) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyImage() {
    Assert::equals('', $this->db()->query('select cast("" as image) as value')->next('value'));
  }

  #[Test]
  public function selectImage() {
    Assert::equals('test', $this->db()->query('select cast("test" as image) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautImage() {
    Assert::equals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as image) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullImage() {
    Assert::equals(null, $this->db()->query('select cast(NULL as image) as value')->next('value'));
  }


  #[Test]
  public function selectEmptyBinary() {
    Assert::equals('', $this->db()->query('select cast("" as binary) as value')->next('value'));
  }

  #[Test]
  public function selectBinary() {
    Assert::equals('test', $this->db()->query('select cast("test" as binary) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautBinary() {
    Assert::equals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as binary) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullBinary() {
    Assert::equals(null, $this->db()->query('select cast(NULL as binary) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarBinary() {
    Assert::equals('', $this->db()->query('select cast("" as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectVarBinary() {
    Assert::equals('test', $this->db()->query('select cast("test" as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautVarBinary() {
    Assert::equals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as varbinary) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullVarBinary() {
    Assert::equals(null, $this->db()->query('select cast(NULL as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectMoney() {
    Assert::equals(0.5, $this->db()->query('select $0.5 as value')->next('value'));
  }

  #[Test]
  public function selectHugeMoney() {
    Assert::equals(2147483648.0, $this->db()->query('select $2147483648 as value')->next('value'));
  }

  #[Test]
  public function selectMoneyOne() {
    Assert::equals(1.0, $this->db()->query('select $1.0 as value')->next('value'));
  }

  #[Test]
  public function selectMoneyZero() {
    Assert::equals(0.0, $this->db()->query('select $0.0 as value')->next('value'));
  }

  #[Test]
  public function selectNegativeMoney() {
    Assert::equals(-6.1, $this->db()->query('select -$6.1 as value')->next('value'));
  }

  #[Test]
  public function selectUnsignedInt() {
    Assert::equals(1, $this->db()->query('select cast(1 as unsigned integer) as value')->next('value'));
  }

  #[Test]
  public function selectMaxUnsignedBigInt() {
    Assert::equals('18446744073709551615', $this->db()->query('select cast(18446744073709551615 as unsigned bigint) as value')->next('value'));
  }

  #[Test]
  public function selectTinyint() {
    Assert::equals(5, $this->db()->query('select cast(5 as tinyint) as value')->next('value'));
  }

  #[Test]
  public function selectTinyintOne() {
    Assert::equals(1, $this->db()->query('select cast(1 as tinyint) as value')->next('value'));
  }

  #[Test]
  public function selectTinyintZero() {
    Assert::equals(0, $this->db()->query('select cast(0 as tinyint) as value')->next('value'));
  }

  #[Test]
  public function selectSmallint() {
    Assert::equals(5, $this->db()->query('select cast(5 as smallint) as value')->next('value'));
  }

  #[Test]
  public function selectSmallintOne() {
    Assert::equals(1, $this->db()->query('select cast(1 as smallint) as value')->next('value'));
  }

  #[Test]
  public function selectSmallintZero() {
    Assert::equals(0, $this->db()->query('select cast(0 as smallint) as value')->next('value'));
  }

  #[Test]
  public function observe() {
    $observer= new class() implements Observer {
      public $observations= [];

      public function numberOfObservations() { return sizeof($this->observations); }

      public function observationAt($i) { return $this->observations[$i]['arg']; }

      public function update($obs, $arg= null) {
        $this->observations[]= ['observable' => $obs, 'arg' => $arg];
      }
    };
    
    $db= $this->db();
    $db->addObserver($observer);
    $db->query('select 1');
    
    Assert::equals(2, $observer->numberOfObservations());
    
    with ($o0= $observer->observationAt(0)); {
      Assert::instance(DBEvent::class, $o0);
      Assert::equals('query', $o0->getName());
      Assert::equals('select 1', $o0->getArgument());
    }

    with ($o1= $observer->observationAt(1)); {
      Assert::instance(DBEvent::class, $o1);
      Assert::equals('queryend', $o1->getName());
      Assert::instance(ResultSet::class, $o1->getArgument());
    }
  }

  #[Test]
  public function rolledBackTransaction() {
    $this->createTransactionsTable($this->tableName());
    $db= $this->db();

    $tran= $db->begin(new \rdbms\Transaction('test'));
    $db->insert('into %c values (1, "should_not_be_here")', $this->tableName());
    $tran->rollback();
    
    Assert::equals(
      [], 
      $db->select('* from %c',$this->tableName())
    );
  }


  #[Test]
  public function committedTransaction() {
    $this->createTransactionsTable($this->tableName());
    $db= $this->db();

    $tran= $db->begin(new \rdbms\Transaction('test'));
    $db->insert('into %c values (1, "should_be_here")', $this->tableName());
    $tran->commit();
    
    Assert::equals(
      [['pk' => 1, 'username' => 'should_be_here']], 
      $db->select('* from %c', $this->tableName())
    );
  }

  #[Test]
  public function consecutiveQueryDoesNotAffectBufferedResults() {
    $this->createTable();
    $db= $this->db();

    $result= $db->query('select * from %c where pk = 2', $this->tableName());
    $db->query('update %c set username = "test" where pk = 1', $this->tableName());

    Assert::equals([['pk' => 2, 'username' => 'kiesel']], iterator_to_array($result));
  }

  #[Test]
  public function unbufferedReadNoResults() {
    $this->createTable();
    $db= $this->db();

    $db->open('select * from %c', $this->tableName());

    Assert::equals(1, $db->query('select 1 as num')->next('num'));
  }
  
  #[Test]
  public function unbufferedReadOneResult() {
    $this->createTable();
    $db= $this->db();

    $q= $db->open('select * from %c', $this->tableName());
    Assert::equals(['pk' => 1, 'username' => 'kiesel'], $q->next());

    Assert::equals(1, $db->query('select 1 as num')->next('num'));
  }

  #[Test, Expect(SQLException::class)]
  public function arithmeticOverflowWithQuery() {
    $this->db()->query('select cast(10000000000000000 as int)')->next();
  }

  #[Test, Expect(SQLException::class)]
  public function arithmeticOverflowWithOpen() {
    $this->db()->open('select cast(10000000000000000 as int)')->next();
  }

  #[Test]
  public function readingRowFailsWithQuery() {
    $q= $this->db()->query($this->rowFailureFixture());
    $records= [];
    do {
      try {
        $r= $q->next('i');
        if ($r) $records[]= $r;
      } catch (\rdbms\SQLException $e) {
        $records[]= false;
      }
    } while ($r);
    Assert::equals([1, false], $records);
  }

  #[Test]
  public function readingRowFailsWithOpen() {
    $q= $this->db()->open($this->rowFailureFixture());
    $records= [];
    do {
      try {
        $r= $q->next('i');
        if ($r) $records[]= $r;
      } catch (\rdbms\SQLException $e) {
        $records[]= false;
      }
    } while ($r);
    Assert::equals([1, false], $records);
  }
}