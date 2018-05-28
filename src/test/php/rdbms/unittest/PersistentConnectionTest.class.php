<?php namespace rdbms\unittest;

use unittest\TestCase;
use rdbms\PersistentConnection;
use rdbms\DriverManager;
use rdbms\Transaction;
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
