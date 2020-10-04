<?php namespace rdbms\unittest\integration;

use lang\{MethodNotImplementedException, Throwable};
use rdbms\{DBEvent, DSN, DriverManager, ResultSet, SQLConnectException, SQLException, SQLStateException, SQLStatementFailedException};
use unittest\{Expect, PrerequisitesNotMetError, Test, TestCase};
use util\{Bytes, Date, Observer};

/**
 * Base class for all RDBMS integration tests
 */
abstract class RdbmsIntegrationTest extends TestCase {
  private $dsn, $conn;

  /** @return void */
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
    $this->assertEquals(true, $this->conn->connect());
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
    $this->assertEquals(
      [['foo' => 1]], 
      $this->db()->select('1 as foo')
    );
  }

  #[Test]
  public function queryAndNext() {
    $q= $this->db()->query('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(['foo' => 1], $q->next());
  }
 
  #[Test]
  public function queryAndNextWithField() {
    $q= $this->db()->query('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(1, $q->next('foo'));
  }

  #[Test]
  public function openAndNext() {
    $q= $this->db()->open('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(['foo' => 1], $q->next());
  }

  #[Test]
  public function openAndNextWithField() {
    $q= $this->db()->open('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(1, $q->next('foo'));
  }
 
  #[Test]
  public function emptyQuery() {
    $this->createTable();
    $q= $this->db()->query('select * from %c where 1 = 0', $this->tableName());
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertNull($q->next());
  }

  #[Test]
  public function insertViaQuery() {
    $this->createTable();
    $this->assertTrue($this->db()->query('insert into %c values (1, "kiesel")', $this->tableName())->isSuccess());
  }

  #[Test]
  public function insertIntoTable() {
    $this->createTable();
    $this->assertEquals(1, $this->db()->insert('into %c values (2, "xp")', $this->tableName()));
  }

  #[Test]
  public function updateViaQuery() {
    $this->createTable();
    $this->assertTrue($this->db()->query('update %c set pk= pk+ 1 where pk= 2', $this->tableName())->isSuccess());
  }
  
  #[Test]
  public function updateTable() {
    $this->createTable();
    $this->assertEquals(1, $this->db()->update('%c set pk= pk+ 1 where pk= 1', $this->tableName()));
  }

  #[Test]
  public function deleteViaQuery() {
    $this->createTable();
    $this->assertTrue($this->db()->query('delete from %c where pk= 2', $this->tableName())->isSuccess());
  }
  
  #[Test]
  public function deleteFromTable() {
    $this->createTable();
    $this->assertEquals(1, $this->db()->delete('from %c where pk= 1', $this->tableName()));
  }
  
  #[Test]
  public function identity() {
    $this->createAutoIncrementTable($this->tableName());      
    $this->assertEquals(1, $this->db()->insert('into %c (username) values ("kiesel")', $this->tableName()));
    $first= $this->db()->identity('unittest_pk_seq');
    
    $this->assertEquals(1, $this->db()->insert('into %c (username) values ("kiesel")', $this->tableName()));
    $this->assertEquals($first+ 1, $this->db()->identity('unittest_pk_seq'));
  }
  
  #[Test, Expect(SQLStatementFailedException::class)]
  public function malformedStatement() {
    $this->db()->query('select insert into delete.');
  }

  #[Test]
  public function selectNull() {
    $this->assertEquals(null, $this->db()->query('select NULL as value')->next('value'));
  }
  
  #[Test]
  public function selectInteger() {
    $this->assertEquals(1, $this->db()->query('select 1 as value')->next('value'));
  }

  #[Test]
  public function selectIntegerZero() {
    $this->assertEquals(0, $this->db()->query('select 0 as value')->next('value'));
  }

  #[Test]
  public function selectNegativeInteger() {
    $this->assertEquals(-6100, $this->db()->query('select -6100 as value')->next('value'));
  }

  #[Test]
  public function selectString() {
    $this->assertEquals('Hello, World!', $this->db()->query('select "Hello, World!" as value')->next('value'));
  }

  #[Test]
  public function selectEmptyString() {
    $this->assertEquals('', $this->db()->query('select "" as value')->next('value'));
  }

  #[Test]
  public function selectSpace() {
    $this->assertEquals(' ', $this->db()->query('select " " as value')->next('value'));
  }

  #[Test]
  public function selectUmlautString() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select %s as value', 'Übercoder')->next('value'))
    );
  }
  
  #[Test]
  public function selectDecimalLiteral() {
    $this->assertEquals(0.5, $this->db()->query('select 0.5 as value')->next('value'));
  }

  #[Test]
  public function selectDecimalLiteralOne() {
    $this->assertEquals(1.0, $this->db()->query('select 1.0 as value')->next('value'));
  }

  #[Test]
  public function selectDecimalLiteralZero() {
    $this->assertEquals(0.0, $this->db()->query('select 0.0 as value')->next('value'));
  }

  #[Test]
  public function selectNegativeDecimalLiteral() {
    $this->assertEquals(-6.1, $this->db()->query('select -6.1 as value')->next('value'));
  }
  
  #[Test]
  public function selectFloat() {
    $this->assertEquals(0.5, $this->db()->query('select cast(0.5 as float) as value')->next('value'));
  }

  #[Test]
  public function selectFloatOne() {
    $this->assertEquals(1.0, $this->db()->query('select cast(1.0 as float) as value')->next('value'));
  }

  #[Test]
  public function selectFloatZero() {
    $this->assertEquals(0.0, $this->db()->query('select cast(0.0 as float) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeFloat() {
    $this->assertEquals(-6.1, round($this->db()->query('select cast(-6.1 as float) as value')->next('value'), 1));
  }

  #[Test]
  public function selectReal() {
    $this->assertEquals(0.5, $this->db()->query('select cast(0.5 as real) as value')->next('value'));
  }

  #[Test]
  public function selectRealOne() {
    $this->assertEquals(1.0, $this->db()->query('select cast(1.0 as real) as value')->next('value'));
  }

  #[Test]
  public function selectRealZero() {
    $this->assertEquals(0.0, $this->db()->query('select cast(0.0 as real) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeReal() {
    $this->assertEquals(-6.1, round($this->db()->query('select cast(-6.1 as real) as value')->next('value'), 1));
  }
  
  #[Test]
  public function selectDate() {
    $cmp= new \util\Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select cast(%s as date) as value', $cmp)->next('value');
    
    $this->assertInstanceOf(Date::class, $result);
    $this->assertEquals($cmp->toString('Y-m-d'), $result->toString('Y-m-d'));
  }

  #[Test]
  public function selectNumericNull() {
    $this->assertEquals(null, $this->db()->query('select convert(numeric(8), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectNumeric() {
    $this->assertEquals(1, $this->db()->query('select convert(numeric(8), 1) as value')->next('value'));
  }

  #[Test]
  public function selectNumericZero() {
    $this->assertEquals(0, $this->db()->query('select convert(numeric(8), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeNumeric() {
    $this->assertEquals(-6100, $this->db()->query('select convert(numeric(8), -6100) as value')->next('value'));
  }

  #[Test]
  public function selectNumericWithScaleNull() {
    $this->assertEquals(null, $this->db()->query('select convert(numeric(8, 2), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectNumericWithScale() {
    $this->assertEquals(1.00, $this->db()->query('select convert(numeric(8, 2), 1) as value')->next('value'));
  }

  #[Test]
  public function selectNumericWithScaleZero() {
    $this->assertEquals(0.00, $this->db()->query('select convert(numeric(8, 2), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeNumericWithScale() {
    $this->assertEquals(-6100.00, $this->db()->query('select convert(numeric(8, 2), -6100) as value')->next('value'));
  }

  #[Test]
  public function select64BitLongMaxPlus1Numeric() {
    $this->assertEquals('9223372036854775808', $this->db()->query('select convert(numeric(20), 9223372036854775808) as value')->next('value'));
  }

  #[Test]
  public function select64BitLongMinMinus1Numeric() {
    $this->assertEquals('-9223372036854775809', $this->db()->query('select convert(numeric(20), -9223372036854775809) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalNull() {
    $this->assertEquals(null, $this->db()->query('select convert(decimal(8), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectDecimal() {
    $this->assertEquals(1, $this->db()->query('select convert(decimal(8), 1) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalZero() {
    $this->assertEquals(0, $this->db()->query('select convert(decimal(8), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeDecimal() {
    $this->assertEquals(-6100, $this->db()->query('select convert(decimal(8), -6100) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalWithScaleNull() {
    $this->assertEquals(null, $this->db()->query('select convert(decimal(8, 2), NULL) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalWithScale() {
    $this->assertEquals(1.00, $this->db()->query('select convert(decimal(8, 2), 1) as value')->next('value'));
  }

  #[Test]
  public function selectDecimalWithScaleZero() {
    $this->assertEquals(0.00, $this->db()->query('select convert(decimal(8, 2), 0) as value')->next('value'));
  }

  #[Test]
  public function selectNegativeDecimalWithScale() {
    $this->assertEquals(-6100.00, $this->db()->query('select convert(decimal(8, 2), -6100) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyChar() {
    $this->assertEquals('    ', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectCharWithoutPadding() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectCharWithPadding() {
    $this->assertEquals('t   ', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarChar() {
    $this->assertEquals('', $this->db()->query('select cast("" as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectVarChar() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectNullVarChar() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as varchar(255)) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyText() {
    $this->assertEquals('', $this->db()->query('select cast("" as text) as value')->next('value'));
  }

  #[Test]
  public function selectText() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as text) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautText() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as text) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNulltext() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as text) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyImage() {
    $this->assertEquals('', $this->db()->query('select cast("" as image) as value')->next('value'));
  }

  #[Test]
  public function selectImage() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as image) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautImage() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as image) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullImage() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as image) as value')->next('value'));
  }


  #[Test]
  public function selectEmptyBinary() {
    $this->assertEquals('', $this->db()->query('select cast("" as binary) as value')->next('value'));
  }

  #[Test]
  public function selectBinary() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as binary) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautBinary() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as binary) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullBinary() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as binary) as value')->next('value'));
  }

  #[Test]
  public function selectEmptyVarBinary() {
    $this->assertEquals('', $this->db()->query('select cast("" as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectVarBinary() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectUmlautVarBinary() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as varbinary) as value')->next('value'))
    );
  }

  #[Test]
  public function selectNullVarBinary() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as varbinary) as value')->next('value'));
  }

  #[Test]
  public function selectMoney() {
    $this->assertEquals(0.5, $this->db()->query('select $0.5 as value')->next('value'));
  }

  #[Test]
  public function selectHugeMoney() {
    $this->assertEquals(2147483648.0, $this->db()->query('select $2147483648 as value')->next('value'));
  }

  #[Test]
  public function selectMoneyOne() {
    $this->assertEquals(1.0, $this->db()->query('select $1.0 as value')->next('value'));
  }

  #[Test]
  public function selectMoneyZero() {
    $this->assertEquals(0.0, $this->db()->query('select $0.0 as value')->next('value'));
  }

  #[Test]
  public function selectNegativeMoney() {
    $this->assertEquals(-6.1, $this->db()->query('select -$6.1 as value')->next('value'));
  }

  #[Test]
  public function selectUnsignedInt() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as unsigned integer) as value')->next('value'));
  }

  #[Test]
  public function selectMaxUnsignedBigInt() {
    $this->assertEquals('18446744073709551615', $this->db()->query('select cast(18446744073709551615 as unsigned bigint) as value')->next('value'));
  }

  #[Test]
  public function selectTinyint() {
    $this->assertEquals(5, $this->db()->query('select cast(5 as tinyint) as value')->next('value'));
  }

  #[Test]
  public function selectTinyintOne() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as tinyint) as value')->next('value'));
  }

  #[Test]
  public function selectTinyintZero() {
    $this->assertEquals(0, $this->db()->query('select cast(0 as tinyint) as value')->next('value'));
  }

  #[Test]
  public function selectSmallint() {
    $this->assertEquals(5, $this->db()->query('select cast(5 as smallint) as value')->next('value'));
  }

  #[Test]
  public function selectSmallintOne() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as smallint) as value')->next('value'));
  }

  #[Test]
  public function selectSmallintZero() {
    $this->assertEquals(0, $this->db()->query('select cast(0 as smallint) as value')->next('value'));
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
    
    $this->assertEquals(2, $observer->numberOfObservations());
    
    with ($o0= $observer->observationAt(0)); {
      $this->assertInstanceOf(DBEvent::class, $o0);
      $this->assertEquals('query', $o0->getName());
      $this->assertEquals('select 1', $o0->getArgument());
    }

    with ($o1= $observer->observationAt(1)); {
      $this->assertInstanceOf(DBEvent::class, $o1);
      $this->assertEquals('queryend', $o1->getName());
      $this->assertInstanceOf(ResultSet::class, $o1->getArgument());
    }
  }

  #[Test]
  public function rolledBackTransaction() {
    $this->createTransactionsTable($this->tableName());
    $db= $this->db();

    $tran= $db->begin(new \rdbms\Transaction('test'));
    $db->insert('into %c values (1, "should_not_be_here")', $this->tableName());
    $tran->rollback();
    
    $this->assertEquals(
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
    
    $this->assertEquals(
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

    $this->assertEquals([['pk' => 2, 'username' => 'kiesel']], iterator_to_array($result));
  }

  #[Test]
  public function unbufferedReadNoResults() {
    $this->createTable();
    $db= $this->db();

    $db->open('select * from %c', $this->tableName());

    $this->assertEquals(1, $db->query('select 1 as num')->next('num'));
  }
  
  #[Test]
  public function unbufferedReadOneResult() {
    $this->createTable();
    $db= $this->db();

    $q= $db->open('select * from %c', $this->tableName());
    $this->assertEquals(['pk' => 1, 'username' => 'kiesel'], $q->next());

    $this->assertEquals(1, $db->query('select 1 as num')->next('num'));
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
    $this->assertEquals([1, false], $records);
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
    $this->assertEquals([1, false], $records);
  }
}