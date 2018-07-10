<?php namespace rdbms\unittest;

use rdbms\ConnectionManager;
use rdbms\DriverManager;

/**
 * Tests for connection managers with connections programmatically
 * registered.
 *
 * @see   xp://rdbms.ConnectionManager#register
 * @see   xp://net.xp_framework.unittest.rdbms.ConnectionManagerTest
 */
#[@action(new \rdbms\unittest\mock\RegisterMockConnection())]
class RegisteredConnectionManagerTest extends ConnectionManagerTest {

  /**
   * Returns an instance with a given number of DSNs
   *
   * @param   [:string] dsns
   * @return  rdbms.ConnectionManager
   */
  protected function instanceWith($dsns) {
    $cm= ConnectionManager::getInstance();
    foreach ($dsns as $name => $dsn) {
      $conn= DriverManager::getConnection($dsn, false);
      if (false !== ($p= strpos($name, '.'))) {
        $cm->register($conn, substr($name, 0, $p), substr($name, $p+ 1));
      } else {
        $cm->register($conn, $name);
      }
    }
    return $cm;
  }

  #[@test, @ignore('Does not work in this class as we eagerly create connections in instanceWith()')]
  public function invalidDsnScheme() {
    // NOOP
  }

  #[@test]
  public function registerReturnsConnection() {
    $conn= DriverManager::getConnection('mock://user:pass@host/db', false);
    $cm= $this->instanceWith([]);
    
    $this->assertEquals($conn, $cm->register($conn));
  }
 
  #[@test]
  public function registerReturnsConnectionWhenPreviouslyRegistered() {
    $conn= DriverManager::getConnection('mock://user:pass@host/db', false);
    $cm= $this->instanceWith([]);
    $cm->register($conn);

    $this->assertEquals($conn, $cm->register($conn));
  }

  #[@test]
  public function registerOverwritesPreviouslyRegistered() {
    $conn1= DriverManager::getConnection('mock://user:pass@host/db1', false);
    $conn2= DriverManager::getConnection('mock://user:pass@host/db2', false);
    $cm= $this->instanceWith([]);

    $this->assertEquals($conn1, $cm->register($conn1));
    $this->assertEquals($conn2, $cm->register($conn2));

    $this->assertEquals($conn2, $cm->getByHost('host', 0));
  }
}
