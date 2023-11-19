<?php namespace rdbms\unittest;

use lang\XPClass;
use rdbms\unittest\dataset\Job;
use rdbms\unittest\mock\MockConnection;
use rdbms\{DriverManager, Statement};
use unittest\{Assert, Before, After, Test};

class StatementTest {
  public $conn, $peer;

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
    $this->conn= DriverManager::getConnection('mock://mock/JOBS?autoconnect=1');
    $this->peer= Job::getPeer();
    $this->peer->setConnection(DriverManager::getConnection('mock://mock/JOBS?autoconnect=1'));
  }
  
  /**
   * Helper method that will call executeSelect() on the passed statement and
   * compare the resulting string to the expected string.
   *
   * @param   string sql
   * @param   rdbms.Statement statement
   * @throws  unittest.AssertionFailedError
   */
  protected function assertStatement($sql, $statement) {
    $statement->executeSelect($this->conn, $this->peer);
    Assert::equals($sql, trim($this->conn->getStatement(), ' '));
  }

  #[Test]
  public function simpleStatement() {
    $this->assertStatement('select * from job', new Statement('select * from job'));
  }
  
  #[Test]
  public function tokenizedStatement() {
    $this->assertStatement(
      'select * from job where job_id= 1',
      new Statement('select * from job where job_id= %d', 1)
    );
  }
  
  #[Test]
  public function multiTokenStatement() {
    $this->assertStatement(
      'select * from job where job_id= 1 and title= "Test"',
      new Statement('select * from job where job_id= %d and title= %s', 1, 'Test')
    );
  }
}