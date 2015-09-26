<?php namespace rdbms\unittest;
 
use rdbms\Criteria;
use rdbms\DriverManager;
use unittest\TestCase;
use rdbms\join\JoinProcessor;
use rdbms\mysql\MySQLConnection;
use rdbms\unittest\dataset\Job;

/**
 * Test JoinProcessor class
 *
 * Note: We're relying on the connection to be a mysql connection -
 * otherwise, quoting and date representation may change and make
 * this testcase fail.
 *
 * @see      xp://rdbms.join.JoinProcessor
 */
class JoinProcessorTest extends TestCase {

  /**
   * Make Job's peer use mysql
   */
  public function setUp() {
    Job::getPeer()->setConnection(new MySQLConnection(new \rdbms\DSN('mysql://localhost:3306/')));
  }
  
  #[@test]
  public function getAttributeStringTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    $this->assertEquals(
      $jp->getAttributeString(),
      JoinProcessor::FIRST.'.job_id as '.JoinProcessor::FIRST.'_job_id, '
      .JoinProcessor::FIRST.'.title as '.JoinProcessor::FIRST.'_title, '
      .JoinProcessor::FIRST.'.valid_from as '.JoinProcessor::FIRST.'_valid_from, '
      .JoinProcessor::FIRST.'.expire_at as '.JoinProcessor::FIRST.'_expire_at, '
      .JoinProcessor::pathToKey(['PersonJob']).'.person_id as '.JoinProcessor::pathToKey(['PersonJob']).'_person_id, '
      .JoinProcessor::pathToKey(['PersonJob']).'.name as '.JoinProcessor::pathToKey(['PersonJob']).'_name, '
      .JoinProcessor::pathToKey(['PersonJob']).'.job_id as '.JoinProcessor::pathToKey(['PersonJob']).'_job_id, '
      .JoinProcessor::pathToKey(['PersonJob']).'.department_id as '.JoinProcessor::pathToKey(['PersonJob']).'_department_id, '
      .JoinProcessor::pathToKey(['PersonJob', 'Department']).'.department_id as '.JoinProcessor::pathToKey(['PersonJob', 'Department']).'_department_id, '
      .JoinProcessor::pathToKey(['PersonJob', 'Department']).'.name as '.JoinProcessor::pathToKey(['PersonJob', 'Department']).'_name, '
      .JoinProcessor::pathToKey(['PersonJob', 'Department']).'.chief_id as '.JoinProcessor::pathToKey(['PersonJob', 'Department']).'_chief_id'
    );
  }

  #[@test]
  public function getJoinStringTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob' => 'join']);
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    $this->assertEquals(
      'JOBS.job as '.JoinProcessor::FIRST.' LEFT OUTER JOIN JOBS.Person as '.JoinProcessor::pathToKey(['PersonJob']).' on ('.JoinProcessor::FIRST.'.job_id = '.JoinProcessor::pathToKey(['PersonJob']).'.job_id) LEFT JOIN JOBS.Department as '.JoinProcessor::pathToKey(['PersonJob', 'Department']).' on ('.JoinProcessor::pathToKey(['PersonJob']).'.department_id = '.JoinProcessor::pathToKey(['PersonJob', 'Department']).'.department_id) where ',
      $jp->getJoinString()
    );
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function emptyModeTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes([]);
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function noJoinModeTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['JobPerson.Department' => 'select']);
  }

  #[@test, @expect('lang.IllegalArgumentException')]
  public function noSuchRoleTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['UnknownRole' => 'join']);
  }
}
