<?php namespace rdbms\unittest;
 
use rdbms\DriverManager;
use rdbms\ResultSet;
use rdbms\SQLConnectException;
use rdbms\SQLConnectionClosedException;
use rdbms\SQLStateException;
use rdbms\SQLStatementFailedException;
use rdbms\unittest\mock\MockResultSet;
use unittest\TestCase;

/**
 * Test rdbms API
 */
#[@action(new \rdbms\unittest\mock\RegisterMockConnection())]
class DBTest extends TestCase {
  protected $conn = null;
    
  /**
   * Setup function
   */
  public function setUp() {
    $this->conn= DriverManager::getConnection('mock://mock/MOCKDB?autoconnect=0');
    $this->assertEquals(0, $this->conn->flags & DB_AUTOCONNECT);
  }
  
  /**
   * Tear down function
   */
  public function tearDown() {
    $this->conn->close();
  }

  /**
   * Asserts a query works
   *
   * @throws  unittest.AssertionFailedError
   */
  protected function assertQuery() {
    $version= '$Revision$';
    $this->conn->setResultSet(new MockResultSet([['version' => $version]]));
    if (
      ($r= $this->conn->query('select %s as version', $version)) &&
      ($this->assertInstanceOf(ResultSet::class, $r)) && 
      ($field= $r->next('version'))
    ) $this->assertEquals($field, $version);
  }

  #[@test]
  public function connect() {
    $result= $this->conn->connect();
    $this->assertTrue($result);
  }

  #[@test, @expect(SQLConnectException::class)]
  public function connectFailure() {
    $this->conn->makeConnectFail('Unknown server');
    $this->conn->connect();
  }
  
  #[@test]
  public function select() {
    $this->conn->connect();
    $this->assertQuery();
  }

  #[@test, @expect(SQLStateException::class)]
  public function queryOnUnConnected() {
    $this->conn->query('select 1');   // Not connected
  }

  #[@test, @expect(SQLStateException::class)]
  public function queryOnDisConnected() {
    $this->conn->connect();
    $this->assertQuery();
    $this->conn->close();
    $this->conn->query('select 1');   // Not connected
  }

  #[@test, @expect(SQLConnectionClosedException::class)]
  public function connectionLost() {
    $this->conn->connect();
    $this->assertQuery();
    $this->conn->letServerDisconnect();
    $this->conn->query('select 1');   // Not connected
  }

  #[@test, @expect(SQLStateException::class)]
  public function queryOnFailedConnection() {
    $this->conn->connections->automatic(true);

    $this->conn->makeConnectFail('Access denied');
    try {
      $this->conn->connect();
    } catch (\rdbms\SQLConnectException $ignored) { }

    $this->conn->query('select 1');   // Previously failed to connect
  }

  #[@test, @expect(SQLStatementFailedException::class)]
  public function statementFailed() {
    $this->conn->connect();
    $this->conn->makeQueryFail('Deadlock', 1205);
    $this->conn->query('select 1');
  }
}
