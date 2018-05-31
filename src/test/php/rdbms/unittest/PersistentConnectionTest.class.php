<?php namespace rdbms\unittest;

use unittest\TestCase;
use rdbms\PersistentConnection;
use rdbms\DriverManager;
use rdbms\Transaction;
use rdbms\SQLStatementFailedException;
use rdbms\SQLConnectionClosedException;
use rdbms\unittest\mock\RegisterMockConnection;
use rdbms\unittest\mock\MockResultSet;

#[@action(new RegisterMockConnection())]
class PersistentConnectionTest extends TestCase {

  #[@test]
  public function can_create_with_dsn() {
    new PersistentConnection('mock://test');
  }

  #[@test]
  public function can_create_with_connection() {
    new PersistentConnection(DriverManager::getConnection('mock://test'));
  }

  #[@test]
  public function connect() {
    $conn= DriverManager::getConnection('mock://test');
    $fixture= new PersistentConnection($conn);
    $fixture->connect();
    $this->assertTrue($conn->isConnected());
  }

  #[@test]
  public function close() {
    $conn= DriverManager::getConnection('mock://test');
    $fixture= new PersistentConnection($conn);
    $fixture->connect();
    $fixture->close();
    $this->assertFalse($conn->isConnected());
  }

  #[@test]
  public function identity() {
    $conn= DriverManager::getConnection('mock://test');
    $conn->setIdentityValue(6100);
    $this->assertEquals(6100, (new PersistentConnection($conn))->identity());
  }

  #[@test]
  public function query_with_params() {
    $conn= DriverManager::getConnection('mock://test');
    (new PersistentConnection($conn))->query('select * from test where id = %d', 6100);
    $this->assertEquals('select * from test where id = 6100', $conn->getStatement());
  }

  #[@test]
  public function open_with_params() {
    $conn= DriverManager::getConnection('mock://test');
    (new PersistentConnection($conn))->open('select * from test where id = %d', 6100);
    $this->assertEquals('select * from test where id = 6100', $conn->getStatement());
  }

  #[@test, @expect(class= SQLStatementFailedException::class, withMessage= 'Syntax error')]
  public function query_failures_thrown() {
    $conn= DriverManager::getConnection('mock://test');
    $conn->makeQueryFail(1000, 'Syntax error');
    (new PersistentConnection($conn))->query('select');
  }

  #[@test]
  public function reconnects_and_reruns_query_when_server_disconnects() {
    $conn= DriverManager::getConnection('mock://test');
    $conn->connect();
    $conn->addResultSet(new MockResultSet([['id' => 'Test']]));
    $conn->letServerDisconnect();

    $fixture= new PersistentConnection($conn);
    $this->assertEquals('Test', $fixture->query('select id from test')->next('id'));
  }

  #[@test, @expect(SQLConnectionClosedException::class)]
  public function does_not_rerun_query_inside_transaction() {
    $conn= DriverManager::getConnection('mock://test');
    $conn->connect();

    $fixture= new PersistentConnection($conn);
    $tran= $fixture->begin(new Transaction('test'));

    $conn->letServerDisconnect();
    $fixture->query('select id from test');
  }
}
