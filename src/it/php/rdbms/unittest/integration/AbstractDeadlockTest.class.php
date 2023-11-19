<?php namespace rdbms\unittest\integration;

use lang\{Runtime, Throwable};
use rdbms\DriverManager;
use unittest\Assert;
use unittest\{PrerequisitesNotMetError, Test, TestCase};

/**
 * Abstract deadlock test
 *
 */
abstract class AbstractDeadlockTest {
  private $dsn;

  /** @return string */
  protected abstract function driverName();

  /**
   * Retrieve database connection object
   *
   * @param   bool connect default TRUE
   * @return  rdbms.DBConnection
   */
  protected function db($connect= true) {
    with ($db= DriverManager::getConnection($this->dsn)); {
      if ($connect) $db->connect();
      return $db;
    }
  }

  /** @return void */
  #[Before]
  public function setUp() {
    $env= strtoupper($this->driverName()).'_DSN';
    if (!($this->dsn= getenv($env))) {
      throw new PrerequisitesNotMetError('No credentials for '.nameof($this).', use '.$env.' to set');
    }

    try {
      $this->dropTables();
      $this->createTables();
    } catch (Throwable $e) {
      throw new PrerequisitesNotMetError($e->getMessage(), $e);
    }
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
    } catch (\rdbms\SQLStatementFailedException $ignored) {}
    
    try {
      $db->query('drop table table_b');
    } catch (\rdbms\SQLStatementFailedException $ignored) {}
    
    $db->close();
  }
  
  /**
   * Start new SQLRunner process
   *
   * @return  lang.Process
   */
  protected function newProcess() {
    with ($rt= Runtime::getInstance()); {
      $proc= $rt->newInstance(
        $rt->startupOptions(),
        'class',
        'rdbms.unittest.integration.SQLRunner',
        [$this->dsn]
      );
      Assert::equals('! Started', $proc->out->readLine());
      return $proc;
    }
  }

  #[Test]
  public function provokeDeadlock() {
    $a= $this->newProcess();
    $b= $this->newProcess();
    
    $a->in->write("update table_a set pk= pk+1\n");
    $b->in->write("update table_b set pk= pk+1\n");
    
    // Reads "+ OK", on each process
    $a->out->readLine();
    $b->out->readLine();
    
    // Now, process a hangs, waiting for lock to table_b
    $a->in->write("update table_b set pk= pk+1\n");
    
    // Finalize the deadlock situation, so the database can
    // detect it.
    $b->in->write("update table_a set pk= pk+1\n");
    
    $a->in->close();
    $b->in->close();
    
    $result= [
      $a->out->readLine(),
      $b->out->readLine()
    ];
    sort($result);
    
    // Cleanup
    $a->close(); $b->close();
    
    // Assert one process succeeds, the other catches a deadlock exception
    // We can't tell which one will do what, though.
    Assert::equals(['+ OK', '- rdbms.SQLDeadlockException'], $result);
  }
}