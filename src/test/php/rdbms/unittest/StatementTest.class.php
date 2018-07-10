<?php namespace rdbms\unittest;
 
use rdbms\DriverManager;
use rdbms\Statement;
use rdbms\unittest\dataset\Job;
use unittest\TestCase;

/**
 * Test Statement class
 *
 * @see   xp://rdbms.Statement
 */
#[@action(new \rdbms\unittest\mock\RegisterMockConnection())]
class StatementTest extends TestCase {
  public $conn= null;
  public $peer= null;

  /**
   * Setup method
   */
  public function setUp() {
    $this->conn= DriverManager::getConnection('mock://mock/JOBS', false);
    $this->peer= Job::getPeer();
    $this->peer->setConnection($this->conn);
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
    $this->assertEquals($sql, trim($this->conn->getStatement(), ' '));
  }

  #[@test]
  public function simpleStatement() {
    $this->assertStatement('select * from job', new Statement('select * from job'));
  }
  
  #[@test]
  public function tokenizedStatement() {
    $this->assertStatement(
      'select * from job where job_id= 1',
      new Statement('select * from job where job_id= %d', 1)
    );
  }
  
  #[@test]
  public function multiTokenStatement() {
    $this->assertStatement(
      'select * from job where job_id= 1 and title= "Test"',
      new Statement('select * from job where job_id= %d and title= %s', 1, 'Test')
    );
  }
}
