<?php namespace rdbms\unittest\integration;

use util\Date;
use rdbms\ResultSet;
use rdbms\DSN;
use rdbms\DBEvent;
use rdbms\SQLStateException;
use rdbms\SQLConnectException;
use rdbms\SQLStatementFailedException;
use rdbms\SQLException;
use util\Observer;
use unittest\TestCase;
use unittest\PrerequisitesNotMetError;
use rdbms\DriverManager;
use util\Bytes;
use lang\Throwable;
use lang\MethodNotImplementedException;

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
    $this->conn->close();
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

  #[@test, @expect(SQLStateException::class)]
  public function noQueryWhenNotConnected() {
    $this->conn->flags ^= DB_AUTOCONNECT;
    $this->conn->query('select 1');
  }
  
  #[@test, @expect(SQLConnectException::class)]
  public function connectFailedThrowsException() {
    $dsn= new DSN($this->dsn);
    $dsn->url->setUser('wrong-user');
    $dsn->url->setPassword('wrong-password');

    DriverManager::getConnection($dsn)->connect();
  }
  
  #[@test]
  public function connect() {
    $this->assertEquals(true, $this->conn->connect());
  }

  #[@test, @expect(SQLStateException::class)]
  public function noQueryWhenDisConnected() {
    $this->conn->flags ^= DB_AUTOCONNECT;
    $this->conn->connect();
    $this->conn->close();
    $this->conn->query('select 1');
  }
  
  #[@test]
  public function simpleSelect() {
    $this->assertEquals(
      [['foo' => 1]], 
      $this->db()->select('1 as foo')
    );
  }

  #[@test]
  public function queryAndNext() {
    $q= $this->db()->query('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(['foo' => 1], $q->next());
  }
 
  #[@test]
  public function queryAndNextWithField() {
    $q= $this->db()->query('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(1, $q->next('foo'));
  }

  #[@test]
  public function openAndNext() {
    $q= $this->db()->open('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(['foo' => 1], $q->next());
  }

  #[@test]
  public function openAndNextWithField() {
    $q= $this->db()->open('select 1 as foo');
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertEquals(1, $q->next('foo'));
  }
 
  #[@test]
  public function emptyQuery() {
    $this->createTable();
    $q= $this->db()->query('select * from %c where 1 = 0', $this->tableName());
    $this->assertInstanceOf(ResultSet::class, $q);
    $this->assertNull($q->next());
  }

  #[@test]
  public function insertViaQuery() {
    $this->createTable();
    $this->assertTrue($this->db()->query('insert into %c values (1, "kiesel")', $this->tableName())->isSuccess());
  }

  #[@test]
  public function insertIntoTable() {
    $this->createTable();
    $this->assertEquals(1, $this->db()->insert('into %c values (2, "xp")', $this->tableName()));
  }

  #[@test]
  public function updateViaQuery() {
    $this->createTable();
    $this->assertTrue($this->db()->query('update %c set pk= pk+ 1 where pk= 2', $this->tableName())->isSuccess());
  }
  
  #[@test]
  public function updateTable() {
    $this->createTable();
    $this->assertEquals(1, $this->db()->update('%c set pk= pk+ 1 where pk= 1', $this->tableName()));
  }

  #[@test]
  public function deleteViaQuery() {
    $this->createTable();
    $this->assertTrue($this->db()->query('delete from %c where pk= 2', $this->tableName())->isSuccess());
  }
  
  #[@test]
  public function deleteFromTable() {
    $this->createTable();
    $this->assertEquals(1, $this->db()->delete('from %c where pk= 1', $this->tableName()));
  }
  
  #[@test]
  public function identity() {
    $this->createAutoIncrementTable($this->tableName());      
    $this->assertEquals(1, $this->db()->insert('into %c (username) values ("kiesel")', $this->tableName()));
    $first= $this->db()->identity('unittest_pk_seq');
    
    $this->assertEquals(1, $this->db()->insert('into %c (username) values ("kiesel")', $this->tableName()));
    $this->assertEquals($first+ 1, $this->db()->identity('unittest_pk_seq'));
  }
  
  #[@test, @expect(SQLStatementFailedException::class)]
  public function malformedStatement() {
    $this->db()->query('select insert into delete.');
  }

  #[@test]
  public function selectNull() {
    $this->assertEquals(null, $this->db()->query('select NULL as value')->next('value'));
  }
  
  #[@test]
  public function selectInteger() {
    $this->assertEquals(1, $this->db()->query('select 1 as value')->next('value'));
  }

  #[@test]
  public function selectIntegerZero() {
    $this->assertEquals(0, $this->db()->query('select 0 as value')->next('value'));
  }

  #[@test]
  public function selectNegativeInteger() {
    $this->assertEquals(-6100, $this->db()->query('select -6100 as value')->next('value'));
  }

  #[@test]
  public function selectString() {
    $this->assertEquals('Hello, World!', $this->db()->query('select "Hello, World!" as value')->next('value'));
  }

  #[@test]
  public function selectEmptyString() {
    $this->assertEquals('', $this->db()->query('select "" as value')->next('value'));
  }

  #[@test]
  public function selectSpace() {
    $this->assertEquals(' ', $this->db()->query('select " " as value')->next('value'));
  }

  #[@test]
  public function selectUmlautString() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select %s as value', 'Übercoder')->next('value'))
    );
  }
  
  #[@test]
  public function selectDecimalLiteral() {
    $this->assertEquals(0.5, $this->db()->query('select 0.5 as value')->next('value'));
  }

  #[@test]
  public function selectDecimalLiteralOne() {
    $this->assertEquals(1.0, $this->db()->query('select 1.0 as value')->next('value'));
  }

  #[@test]
  public function selectDecimalLiteralZero() {
    $this->assertEquals(0.0, $this->db()->query('select 0.0 as value')->next('value'));
  }

  #[@test]
  public function selectNegativeDecimalLiteral() {
    $this->assertEquals(-6.1, $this->db()->query('select -6.1 as value')->next('value'));
  }
  
  #[@test]
  public function selectFloat() {
    $this->assertEquals(0.5, $this->db()->query('select cast(0.5 as float) as value')->next('value'));
  }

  #[@test]
  public function selectFloatOne() {
    $this->assertEquals(1.0, $this->db()->query('select cast(1.0 as float) as value')->next('value'));
  }

  #[@test]
  public function selectFloatZero() {
    $this->assertEquals(0.0, $this->db()->query('select cast(0.0 as float) as value')->next('value'));
  }

  #[@test]
  public function selectNegativeFloat() {
    $this->assertEquals(-6.1, round($this->db()->query('select cast(-6.1 as float) as value')->next('value'), 1));
  }

  #[@test]
  public function selectReal() {
    $this->assertEquals(0.5, $this->db()->query('select cast(0.5 as real) as value')->next('value'));
  }

  #[@test]
  public function selectRealOne() {
    $this->assertEquals(1.0, $this->db()->query('select cast(1.0 as real) as value')->next('value'));
  }

  #[@test]
  public function selectRealZero() {
    $this->assertEquals(0.0, $this->db()->query('select cast(0.0 as real) as value')->next('value'));
  }

  #[@test]
  public function selectNegativeReal() {
    $this->assertEquals(-6.1, round($this->db()->query('select cast(-6.1 as real) as value')->next('value'), 1));
  }
  
  #[@test]
  public function selectDate() {
    $cmp= new \util\Date('2009-08-14 12:45:00');
    $result= $this->db()->query('select cast(%s as date) as value', $cmp)->next('value');
    
    $this->assertInstanceOf(Date::class, $result);
    $this->assertEquals($cmp->toString('Y-m-d'), $result->toString('Y-m-d'));
  }

  #[@test]
  public function selectNumericNull() {
    $this->assertEquals(null, $this->db()->query('select convert(numeric(8), NULL) as value')->next('value'));
  }

  #[@test]
  public function selectNumeric() {
    $this->assertEquals(1, $this->db()->query('select convert(numeric(8), 1) as value')->next('value'));
  }

  #[@test]
  public function selectNumericZero() {
    $this->assertEquals(0, $this->db()->query('select convert(numeric(8), 0) as value')->next('value'));
  }

  #[@test]
  public function selectNegativeNumeric() {
    $this->assertEquals(-6100, $this->db()->query('select convert(numeric(8), -6100) as value')->next('value'));
  }

  #[@test]
  public function selectNumericWithScaleNull() {
    $this->assertEquals(null, $this->db()->query('select convert(numeric(8, 2), NULL) as value')->next('value'));
  }

  #[@test]
  public function selectNumericWithScale() {
    $this->assertEquals(1.00, $this->db()->query('select convert(numeric(8, 2), 1) as value')->next('value'));
  }

  #[@test]
  public function selectNumericWithScaleZero() {
    $this->assertEquals(0.00, $this->db()->query('select convert(numeric(8, 2), 0) as value')->next('value'));
  }

  #[@test]
  public function selectNegativeNumericWithScale() {
    $this->assertEquals(-6100.00, $this->db()->query('select convert(numeric(8, 2), -6100) as value')->next('value'));
  }

  #[@test]
  public function select64BitLongMaxPlus1Numeric() {
    $this->assertEquals('9223372036854775808', $this->db()->query('select convert(numeric(20), 9223372036854775808) as value')->next('value'));
  }

  #[@test]
  public function select64BitLongMinMinus1Numeric() {
    $this->assertEquals('-9223372036854775809', $this->db()->query('select convert(numeric(20), -9223372036854775809) as value')->next('value'));
  }

  #[@test]
  public function selectDecimalNull() {
    $this->assertEquals(null, $this->db()->query('select convert(decimal(8), NULL) as value')->next('value'));
  }

  #[@test]
  public function selectDecimal() {
    $this->assertEquals(1, $this->db()->query('select convert(decimal(8), 1) as value')->next('value'));
  }

  #[@test]
  public function selectDecimalZero() {
    $this->assertEquals(0, $this->db()->query('select convert(decimal(8), 0) as value')->next('value'));
  }

  #[@test]
  public function selectNegativeDecimal() {
    $this->assertEquals(-6100, $this->db()->query('select convert(decimal(8), -6100) as value')->next('value'));
  }

  #[@test]
  public function selectDecimalWithScaleNull() {
    $this->assertEquals(null, $this->db()->query('select convert(decimal(8, 2), NULL) as value')->next('value'));
  }

  #[@test]
  public function selectDecimalWithScale() {
    $this->assertEquals(1.00, $this->db()->query('select convert(decimal(8, 2), 1) as value')->next('value'));
  }

  #[@test]
  public function selectDecimalWithScaleZero() {
    $this->assertEquals(0.00, $this->db()->query('select convert(decimal(8, 2), 0) as value')->next('value'));
  }

  #[@test]
  public function selectNegativeDecimalWithScale() {
    $this->assertEquals(-6100.00, $this->db()->query('select convert(decimal(8, 2), -6100) as value')->next('value'));
  }

  #[@test]
  public function selectEmptyChar() {
    $this->assertEquals('    ', $this->db()->query('select cast("" as char(4)) as value')->next('value'));
  }

  #[@test]
  public function selectCharWithoutPadding() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as char(4)) as value')->next('value'));
  }

  #[@test]
  public function selectCharWithPadding() {
    $this->assertEquals('t   ', $this->db()->query('select cast("t" as char(4)) as value')->next('value'));
  }

  #[@test]
  public function selectEmptyVarChar() {
    $this->assertEquals('', $this->db()->query('select cast("" as varchar(255)) as value')->next('value'));
  }

  #[@test]
  public function selectVarChar() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as varchar(255)) as value')->next('value'));
  }

  #[@test]
  public function selectNullVarChar() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as varchar(255)) as value')->next('value'));
  }

  #[@test]
  public function selectEmptyText() {
    $this->assertEquals('', $this->db()->query('select cast("" as text) as value')->next('value'));
  }

  #[@test]
  public function selectText() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as text) as value')->next('value'));
  }

  #[@test]
  public function selectUmlautText() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as text) as value')->next('value'))
    );
  }

  #[@test]
  public function selectNulltext() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as text) as value')->next('value'));
  }

  #[@test]
  public function selectEmptyImage() {
    $this->assertEquals('', $this->db()->query('select cast("" as image) as value')->next('value'));
  }

  #[@test]
  public function selectImage() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as image) as value')->next('value'));
  }

  #[@test]
  public function selectUmlautImage() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as image) as value')->next('value'))
    );
  }

  #[@test]
  public function selectNullImage() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as image) as value')->next('value'));
  }


  #[@test]
  public function selectEmptyBinary() {
    $this->assertEquals('', $this->db()->query('select cast("" as binary) as value')->next('value'));
  }

  #[@test]
  public function selectBinary() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as binary) as value')->next('value'));
  }

  #[@test]
  public function selectUmlautBinary() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as binary) as value')->next('value'))
    );
  }

  #[@test]
  public function selectNullBinary() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as binary) as value')->next('value'));
  }

  #[@test]
  public function selectEmptyVarBinary() {
    $this->assertEquals('', $this->db()->query('select cast("" as varbinary) as value')->next('value'));
  }

  #[@test]
  public function selectVarBinary() {
    $this->assertEquals('test', $this->db()->query('select cast("test" as varbinary) as value')->next('value'));
  }

  #[@test]
  public function selectUmlautVarBinary() {
    $this->assertEquals(
      new Bytes("\303\234bercoder"),
      new Bytes($this->db()->query('select cast("Übercoder" as varbinary) as value')->next('value'))
    );
  }

  #[@test]
  public function selectNullVarBinary() {
    $this->assertEquals(null, $this->db()->query('select cast(NULL as varbinary) as value')->next('value'));
  }

  #[@test]
  public function selectMoney() {
    $this->assertEquals(0.5, $this->db()->query('select $0.5 as value')->next('value'));
  }

  #[@test]
  public function selectHugeMoney() {
    $this->assertEquals(2147483648.0, $this->db()->query('select $2147483648 as value')->next('value'));
  }

  #[@test]
  public function selectMoneyOne() {
    $this->assertEquals(1.0, $this->db()->query('select $1.0 as value')->next('value'));
  }

  #[@test]
  public function selectMoneyZero() {
    $this->assertEquals(0.0, $this->db()->query('select $0.0 as value')->next('value'));
  }

  #[@test]
  public function selectNegativeMoney() {
    $this->assertEquals(-6.1, $this->db()->query('select -$6.1 as value')->next('value'));
  }

  #[@test]
  public function selectUnsignedInt() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as unsigned integer) as value')->next('value'));
  }

  #[@test]
  public function selectMaxUnsignedBigInt() {
    $this->assertEquals('18446744073709551615', $this->db()->query('select cast(18446744073709551615 as unsigned bigint) as value')->next('value'));
  }

  #[@test]
  public function selectTinyint() {
    $this->assertEquals(5, $this->db()->query('select cast(5 as tinyint) as value')->next('value'));
  }

  #[@test]
  public function selectTinyintOne() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as tinyint) as value')->next('value'));
  }

  #[@test]
  public function selectTinyintZero() {
    $this->assertEquals(0, $this->db()->query('select cast(0 as tinyint) as value')->next('value'));
  }

  #[@test]
  public function selectSmallint() {
    $this->assertEquals(5, $this->db()->query('select cast(5 as smallint) as value')->next('value'));
  }

  #[@test]
  public function selectSmallintOne() {
    $this->assertEquals(1, $this->db()->query('select cast(1 as smallint) as value')->next('value'));
  }

  #[@test]
  public function selectSmallintZero() {
    $this->assertEquals(0, $this->db()->query('select cast(0 as smallint) as value')->next('value'));
  }

  #[@test]
  public function observe() {
    $observer= newinstance(Observer::class, [], [
      'observations' => [],
      'numberOfObservations' => function() {
        return sizeof($this->observations);
      },
      'observationAt' => function($i) {
        return $this->observations[$i]['arg'];
      },
      'update' => function($obs, $arg= null) {
        $this->observations[]= ['observable' => $obs, 'arg' => $arg];
      }
    ]);
    
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

  #[@test]
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


  #[@test]
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

  #[@test]
  public function consecutiveQueryDoesNotAffectBufferedResults() {
    $this->createTable();
    $db= $this->db();

    $result= $db->query('select * from %c where pk = 2', $this->tableName());
    $db->query('update %c set username = "test" where pk = 1', $this->tableName());

    $this->assertEquals([['pk' => 2, 'username' => 'kiesel']], iterator_to_array($result));
  }

  #[@test]
  public function unbufferedReadNoResults() {
    $this->createTable();
    $db= $this->db();

    $db->open('select * from %c', $this->tableName());

    $this->assertEquals(1, $db->query('select 1 as num')->next('num'));
  }
  
  #[@test]
  public function unbufferedReadOneResult() {
    $this->createTable();
    $db= $this->db();

    $q= $db->query('select * from %c', $this->tableName());
    $this->assertEquals(['pk' => 1, 'username' => 'kiesel'], $q->next());

    $this->assertEquals(1, $db->query('select 1 as num')->next('num'));
  }

  #[@test, @expect(SQLException::class)]
  public function arithmeticOverflowWithQuery() {
    $this->db()->query('select cast(10000000000000000 as int)')->next();
  }

  #[@test, @expect(SQLException::class)]
  public function arithmeticOverflowWithOpen() {
    $this->db()->open('select cast(10000000000000000 as int)')->next();
  }

  #[@test]
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

  #[@test]
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
