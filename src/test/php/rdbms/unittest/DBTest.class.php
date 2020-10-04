<?php namespace rdbms\unittest;
 
use rdbms\unittest\mock\{MockResultSet, RegisterMockConnection};
use rdbms\{DriverManager, ResultSet, SQLConnectException, SQLConnectionClosedException, SQLStateException, SQLStatementFailedException};
use unittest\{Expect, Test, TestCase};

/**
 * Test rdbms API
 */
#[Action(eval: 'new RegisterMockConnection()')]
class DBTest extends TestCase {
  protected $conn = null;
    
  /**
   * Setup function
   */
  public function setUp() {
    $this->conn= DriverManager::getConnection('mock://mock/MOCKDB?autoconnect=0');
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

  #[Test]
  public function connect() {
    $result= $this->conn->connect();
    $this->assertTrue($result);
  }

  #[Test, Expect(SQLConnectException::class)]
  public function connectFailure() {
    $this->conn->makeConnectFail('Unknown server');
    $this->conn->connect();
  }
  
  #[Test]
  public function select() {
    $this->conn->connect();
    $this->assertQuery();
  }

  #[Test, Expect(SQLStateException::class)]
  public function queryOnUnConnected() {
    $this->conn->query('select 1');   // Not connected
  }

  #[Test, Expect(SQLStateException::class)]
  public function queryOnDisConnected() {
    $this->conn->connect();
    $this->assertQuery();
    $this->conn->close();
    $this->conn->query('select 1');   // Not connected
  }

  #[Test, Expect(SQLConnectionClosedException::class)]
  public function connectionLost() {
    $this->conn->connections->automatic(true)->reconnect(0);

    $this->conn->connect();
    $this->assertQuery();
    $this->conn->letServerDisconnect();
    $this->conn->query('select 1');   // Not connected
  }

  #[Test]
  public function connection_reestablished() {
    $this->conn->connections->automatic(true)->reconnect(1);

    $this->conn->connect();
    $this->assertQuery();
    $this->conn->letServerDisconnect();
    $this->assertQuery();
  }

  #[Test, Expect(SQLStateException::class)]
  public function queryOnFailedConnection() {
    $this->conn->connections->automatic(true)->reconnect(0);

    $this->conn->makeConnectFail('Access denied');
    try {
      $this->conn->connect();
    } catch (\rdbms\SQLConnectException $ignored) { }

    $this->conn->query('select 1');   // Previously failed to connect
  }

  #[Test, Expect(SQLStatementFailedException::class)]
  public function statementFailed() {
    $this->conn->connect();
    $this->conn->makeQueryFail('Deadlock', 1205);
    $this->conn->query('select 1');
  }
}