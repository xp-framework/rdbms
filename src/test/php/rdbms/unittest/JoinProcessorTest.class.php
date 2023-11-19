<?php namespace rdbms\unittest;

use lang\IllegalArgumentException;
use rdbms\join\JoinProcessor;
use rdbms\mysql\MySQLConnection;
use rdbms\unittest\dataset\Job;
use rdbms\{Criteria, DriverManager, DSN};
use test\{Assert, Before, Expect, Test, TestCase};

/**
 * Test JoinProcessor class
 *
 * Note: We're relying on the connection to be a mysql connection -
 * otherwise, quoting and date representation may change and make
 * this testcase fail.
 *
 * @see      xp://rdbms.join.JoinProcessor
 */
class JoinProcessorTest {

  #[Before]
  public function setUp() {
    Job::getPeer()->setConnection(new MySQLConnection(new DSN('mysql://localhost:3306/')));
  }
  
  #[Test]
  public function getAttributeStringTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    Assert::equals(
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

  #[Test]
  public function getJoinStringTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob' => 'join']);
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    Assert::equals(
      'JOBS.job as '.JoinProcessor::FIRST.' LEFT OUTER JOIN JOBS.Person as '.JoinProcessor::pathToKey(['PersonJob']).' on ('.JoinProcessor::FIRST.'.job_id = '.JoinProcessor::pathToKey(['PersonJob']).'.job_id) LEFT JOIN JOBS.Department as '.JoinProcessor::pathToKey(['PersonJob', 'Department']).' on ('.JoinProcessor::pathToKey(['PersonJob']).'.department_id = '.JoinProcessor::pathToKey(['PersonJob', 'Department']).'.department_id) where ',
      $jp->getJoinString()
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function emptyModeTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes([]);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function noJoinModeTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['JobPerson.Department' => 'select']);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function noSuchRoleTest() {
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['UnknownRole' => 'join']);
  }
}