<?php namespace rdbms\unittest\integration;

use lang\Runtime;
use rdbms\{DriverManager, SQLStatementFailedException};
use test\verify\Condition;
use test\{Assert, Before, After, Test};

#[Condition('self::testDsnSet()')]
abstract class AbstractDeadlockTest {
  protected static $DRIVER= null;
  private $dsn;
  private $close= [];

  /** Verifies [NAME]_DSN */
  public static function testDsnSet() {
    return getenv(strtoupper(static::$DRIVER).'_DSN');
  }

  /** Initialize DSN */
  public function __construct() {
    $this->dsn= self::testDsnSet();
  }

  /**
   * Retrieve database connection object
   *
   * @param  bool $connect default TRUE
   * @return rdbms.DBConnection
   */
  protected function db($connect= true) {
    $conn= DriverManager::getConnection($this->dsn);
    $connect && $conn->connect();
    return $conn;
  }

  /** @return void */
  #[Before]
  public function setUp() {
    $this->dropTables();
    $this->createTables();
  }

  /** @return void */
  #[After]
  public function tearDown() {
    $this->dsn && $this->dropTables();
  }
  
  /**
   * Create necessary tables for this test
   */
  protected function createTables() {
    $db= $this->db();
    
    $db->query('create table table_a (pk int)');
    $db->query('create table table_b (pk int)');
    
    $db->insert('into table_a values (1)');
    $db->insert('into table_a values (2)');

    $db->insert('into table_b values (1)');
    $db->insert('into table_b values (2)');
    
    $db->close();
  }
  
  /**
   * Cleanup database tables
   */
  protected function dropTables() {
    $db= $this->db();
    
    try {
      $db->query('drop table table_a');
    } catch (SQLStatementFailedException $ignored) {}
    
    try {
      $db->query('drop table table_b');
    } catch (SQLStatementFailedException $ignored) {}
    
    $db->close();
  }
  
  /**
   * Start new SQLRunner process
   *
   * @return  lang.Process
   */
  protected function newProcess() {
    $rt= Runtime::getInstance();
    $proc= $rt->newInstance(
      $rt->startupOptions(),
      'class',
      'rdbms.unittest.integration.SQLRunner',
      [$this->dsn]
    );
    Assert::equals('! Started', $proc->out->readLine());
    return $proc;
  }

  #[Test]
  public function provokeDeadlock() {
    $a= $this->newProcess();
    $b= $this->newProcess();
    $result= [];
    
    $a->in->write("update table_a set pk= pk+1\n");
    $b->in->write("update table_b set pk= pk+1\n");
    
    // Reads "+ OK", on each process
    $result[]= $a->out->readLine();
    $result[]= $b->out->readLine();
    
    // Now, process a hangs, waiting for lock to table_b
    $a->in->write("update table_b set pk= pk+1\n");
    
    // Finalize the deadlock situation, so the database can
    // detect it.
    $b->in->write("update table_a set pk= pk+1\n");
    
    $a->in->close();
    $b->in->close();
    
    $result[]= $a->out->readLine();
    $result[]= $b->out->readLine();
    sort($result);
    
    // Cleanup
    $a->close(); $b->close();
    
    // Assert one process succeeds, the other catches a deadlock exception
    // We can't tell which one will do what, though.
    Assert::equals(['+ OK', '+ OK', '+ OK', '- rdbms.SQLDeadlockException'], $result);
  }
}