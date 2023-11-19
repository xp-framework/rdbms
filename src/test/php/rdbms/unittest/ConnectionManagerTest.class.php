<?php namespace rdbms\unittest;

use lang\XPClass;
use rdbms\unittest\mock\MockConnection;
use rdbms\{
  ConnectionManager,
  ConnectionNotRegisteredException,
  DBConnection,
  DriverNotSupportedException,
  DriverManager
};
use test\{Assert, Before, After, Expect, Test};

abstract class ConnectionManagerTest {
  
  /**
   * Returns an instance with a given number of DSNs
   *
   * @param   [:string] dsns
   * @return  rdbms.ConnectionManager
   */
  protected abstract function instanceWith($dsns);

  #[Before]
  public function registerMock() {
    DriverManager::register('mock', new XPClass(MockConnection::class));
  }

  #[After]
  public function removeMock() {
    DriverManager::remove('mock');
  }

  #[Test]
  public function initallyEmpty() {
    ConnectionManager::getInstance()->pool= [];
    Assert::equals([], $this->instanceWith([])->getConnections());
  }

  #[Test]
  public function acquireExistingConnectionViaGetByHost() {
    $cm= $this->instanceWith(['mydb' => 'mock://user:pass@host/db?autoconnect=1']);
    Assert::instance(DBConnection::class, $cm->getByHost('mydb', 0));
  }
  
  #[Test, Expect(ConnectionNotRegisteredException::class)]
  public function acquireNonExistantConnectionViaGetByHost() {
    $cm= $this->instanceWith(['mydb' => 'mock://user:pass@host/db?autoconnect=1']);
    $cm->getByHost('nonexistant', 0);
  }

  #[Test]
  public function acquireExistingConnectionViaGet() {
    $cm= $this->instanceWith(['mydb' => 'mock://user:pass@host/db?autoconnect=1']);
    Assert::instance(DBConnection::class, $cm->getByHost('mydb', 0));
  }
  
  #[Test, Expect(ConnectionNotRegisteredException::class)]
  public function acquireNonExistantConnectionWithExistantUserViaGet() {
    $cm= $this->instanceWith(['mydb' => 'mock://user:pass@host/db?autoconnect=1']);
    $cm->get('nonexistant', 'user');
  }

  #[Test, Expect(ConnectionNotRegisteredException::class)]
  public function acquireExistantConnectionWithNonExistantUserViaGet() {
    $cm= $this->instanceWith(['mydb' => 'mock://user:pass@host/db?autoconnect=1']);
    $cm->get('mydb', 'nonexistant');
  }

  #[Test]
  public function invalidDsnScheme() {
    $this->instanceWith(['mydb' => 'invalid://user:pass@host/db?autoconnect=1']);
  }
  
  #[Test, Expect(DriverNotSupportedException::class)]
  public function acquireInvalidDsnScheme() {
    $cm= $this->instanceWith(['mydb' => 'invalid://user:pass@host/db?autoconnect=1']);
    $cm->getByHost('mydb', 0);
  }

  #[Test]
  public function getByUserAndHost() {
    $dsns= [
      'mydb.user'  => 'mock://user:pass@host/db?autoconnect=1',
      'mydb.admin' => 'mock://admin:pass@host/db?autoconnect=1'
    ];
    $cm= $this->instanceWith($dsns);
    Assert::equals(new \rdbms\DSN($dsns['mydb.user']), $cm->get('mydb', 'user')->dsn);
  }
 
  #[Test]
  public function getFirstByHost() {
    $dsns= [
      'mydb.user'  => 'mock://user:pass@host/db?autoconnect=1',
      'mydb.admin' => 'mock://admin:pass@host/db?autoconnect=1'
    ];
    $cm= $this->instanceWith($dsns);
    Assert::equals(new \rdbms\DSN($dsns['mydb.user']), $cm->getByHost('mydb', 0)->dsn);
  }
 
  #[Test]
  public function getSecondByHost() {
    $dsns= [
      'mydb.user'  => 'mock://user:pass@host/db?autoconnect=1',
      'mydb.admin' => 'mock://admin:pass@host/db?autoconnect=1'
    ];
    $cm= $this->instanceWith($dsns);
    Assert::equals(new \rdbms\DSN($dsns['mydb.admin']), $cm->getByHost('mydb', 1)->dsn);
  }

  #[Test]
  public function getAllByHost() {
    $dsns= [
      'mydb.user'  => 'mock://user:pass@host/db?autoconnect=1',
      'mydb.admin' => 'mock://admin:pass@host/db?autoconnect=1'
    ];
    $cm= $this->instanceWith($dsns);
    
    $values= [];
    foreach ($cm->getByHost('mydb') as $conn) {
      $values[]= $conn->dsn;
    }
    Assert::equals(
      [new \rdbms\DSN($dsns['mydb.user']), new \rdbms\DSN($dsns['mydb.admin'])], 
      $values
    );
  }
}