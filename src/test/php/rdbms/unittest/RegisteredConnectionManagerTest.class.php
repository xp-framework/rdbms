<?php namespace rdbms\unittest;

use rdbms\{ConnectionManager, DriverManager};
use test\Assert;
use test\{Action, Ignore, Test};

/**
 * Tests for connection managers with connections programmatically
 * registered.
 *
 * @see   xp://rdbms.ConnectionManager#register
 * @see   xp://net.xp_framework.unittest.rdbms.ConnectionManagerTest
 */
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
      $conn= DriverManager::getConnection($dsn);
      if (false !== ($p= strpos($name, '.'))) {
        $cm->register($conn, substr($name, 0, $p), substr($name, $p+ 1));
      } else {
        $cm->register($conn, $name);
      }
    }
    return $cm;
  }

  #[Test, Ignore('Does not work in this class as we eagerly create connections in instanceWith()')]
  public function invalidDsnScheme() {
    // NOOP
  }

  #[Test]
  public function registerReturnsConnection() {
    $conn= DriverManager::getConnection('mock://user:pass@host/db');
    $cm= $this->instanceWith([]);
    
    Assert::equals($conn, $cm->register($conn));
  }
 
  #[Test]
  public function registerReturnsConnectionWhenPreviouslyRegistered() {
    $conn= DriverManager::getConnection('mock://user:pass@host/db');
    $cm= $this->instanceWith([]);
    $cm->register($conn);

    Assert::equals($conn, $cm->register($conn));
  }

  #[Test]
  public function registerOverwritesPreviouslyRegistered() {
    $conn1= DriverManager::getConnection('mock://user:pass@host/db1');
    $conn2= DriverManager::getConnection('mock://user:pass@host/db2');
    $cm= $this->instanceWith([]);

    Assert::equals($conn1, $cm->register($conn1));
    Assert::equals($conn2, $cm->register($conn2));

    Assert::equals($conn2, $cm->getByHost('host', 0));
  }
}