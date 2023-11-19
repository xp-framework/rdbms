<?php namespace rdbms\unittest;

use lang\XPClass;
use rdbms\unittest\mock\{MockResultSet, MockConnection};
use rdbms\{DriverManager, ResultSet, SQLConnectException, SQLConnectionClosedException, SQLStateException, SQLStatementFailedException};
use unittest\{Assert, Before, After, Expect, Test};

class DBTest {
  private $close= [];

  #[Before]
  public function registerMock() {
    DriverManager::register('mock', new XPClass(MockConnection::class));
  }

  #[After]
  public function removeMock() {
    DriverManager::remove('mock');
  }

  #[Before]
  public function setUp() {
    $this->conn= DriverManager::getConnection('mock://mock/MOCKDB?autoconnect=0');
  }

  #[After]
  public function disconnect() {
    foreach ($this->close as $conn) {
      $conn->close();
    }
  }

  /**
   * Asserts a query works
   *
   * @throws  unittest.AssertionFailedError
   */
  protected function assertQuery($conn) {
    $version= '$Revision$';
    $conn->setResultSet(new MockResultSet([['version' => $version]]));
    if (
      ($r= $conn->query('select %s as version', $version)) &&
      (Assert::instance(ResultSet::class, $r)) && 
      ($field= $r->next('version'))
    ) Assert::equals($field, $version);
  }

  /** @return rdbms.unittest.mock.MockConnection */
  private function connection() {
    $conn= DriverManager::getConnection('mock://mock/MOCKDB?autoconnect=0');
    $this->close[]= $conn;
    return $conn;
  }

  #[Test]
  public function connect() {
    $result= $this->connection()->connect();
    Assert::true($result);
  }

  #[Test, Expect(SQLConnectException::class)]
  public function connectFailure() {
    $conn= $this->connection();
    $conn->makeConnectFail('Unknown server');
    $conn->connect();
  }
  
  #[Test]
  public function select() {
    $conn= $this->connection();
    $conn->connect();
    $this->assertQuery($conn);
  }

  #[Test, Expect(SQLStateException::class)]
  public function queryOnUnConnected() {
    $this->connection()->query('select 1');   // Not connected
  }

  #[Test, Expect(SQLStateException::class)]
  public function queryOnDisConnected() {
    $conn= $this->connection();
    $conn->connect();
    $this->assertQuery($conn);
    $conn->close();
    $conn->query('select 1');   // Not connected
  }

  #[Test, Expect(SQLConnectionClosedException::class)]
  public function connectionLost() {
    $conn= $this->connection();
    $conn->connections->automatic(true)->reconnect(0);

    $conn->connect();
    $this->assertQuery($conn);
    $conn->letServerDisconnect();
    $conn->query('select 1');   // Not connected
  }

  #[Test]
  public function connection_reestablished() {
    $conn= $this->connection();
    $conn->connections->automatic(true)->reconnect(1);

    $conn->connect();
    $this->assertQuery($conn);
    $conn->letServerDisconnect();
    $this->assertQuery($conn);
  }

  #[Test, Expect(SQLStateException::class)]
  public function queryOnFailedConnection() {
    $conn= $this->connection();
    $conn->connections->automatic(true)->reconnect(0);

    $conn->makeConnectFail('Access denied');
    try {
      $conn->connect();
    } catch (\rdbms\SQLConnectException $ignored) { }

    $conn->query('select 1');   // Previously failed to connect
  }

  #[Test, Expect(SQLStatementFailedException::class)]
  public function statementFailed() {
    $conn= $this->connection();
    $conn->connect();
    $conn->makeQueryFail('Deadlock', 1205);
    $conn->query('select 1');
  }
}