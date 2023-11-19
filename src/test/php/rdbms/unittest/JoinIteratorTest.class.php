<?php namespace rdbms\unittest;

use rdbms\join\{JoinIterator, JoinProcessor};
use rdbms\mysql\MySQLConnection;
use rdbms\unittest\dataset\{Job, Person};
use rdbms\unittest\mock\MockResultSet;
use rdbms\{CachedResults, ConnectionManager, Criteria, DSN};
use unittest\Assert;
use unittest\{BeforeClass, Expect, Test, TestCase};
use util\{Date, NoSuchElementException};

/**
 * Test JoinProcessor class
 *
 * Note: We're relying on the connection to be a mysql connection -
 * otherwise, quoting and date representation may change and make
 * this testcase fail.
 *
 * @see    xp://rdbms.join.JoinIterator
 */
class JoinIteratorTest {
  
  #[BeforeClass]
  public static function registerConnection() {
    ConnectionManager::getInstance()->register(new MySQLConnection(new DSN('mysql://localhost:3306/')), 'jobs');
  }
  
  #[Test, Expect(NoSuchElementException::class)]
  public function emptyResultNextTest() {
    (new JoinIterator(new JoinProcessor(Job::getPeer()), new MockResultSet()))->next();
  }
  
  #[Test]
  public function emptyResultHasNextTest() {
    Assert::false((new JoinIterator(new JoinProcessor(Job::getPeer()), new MockResultSet()))->hasNext());
  }
  
  #[Test]
  public function resultHasNextTest() {
    $rs= new MockResultSet(
      [
        [
          JoinProcessor::FIRST.'_job_id'        => '11',
          JoinProcessor::FIRST.'_title'         => 'clean toilette',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
          't1_person_id'     => '11',
          't1_name'          => 'Schultz',
          't1_job_id'        => '21',
          't1_department_id' => '31',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '11',
          JoinProcessor::FIRST.'_title'         => 'clean toilette',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
          't1_person_id'     => '12',
          't1_name'          => 'Friebe',
          't1_job_id'        => '11',
          't1_department_id' => '31',
        ],
      ]
    );
    $ji= new JoinIterator(new JoinProcessor(Job::getPeer()), $rs);
    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $ji->next());
    Assert::false($ji->hasNext());
  }

  #[Test]
  public function multipleResultTest() {
    $rs= new MockResultSet(
      [
        [
          JoinProcessor::FIRST.'_job_id'        => '11',
          JoinProcessor::FIRST.'_title'         => 'clean toilette',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '11',
          JoinProcessor::FIRST.'_title'         => 'clean toilette',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '12',
          JoinProcessor::FIRST.'_title'         => 'second job',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '13',
          JoinProcessor::FIRST.'_title'         => 'third job',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
        ],
      ]
    );
    $jp= new JoinProcessor(Job::getPeer());
    $ji= new JoinIterator($jp, $rs);
    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $job= $ji->next());
    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $job= $ji->next());
    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $job= $ji->next());
    Assert::false($ji->hasNext());
  }

  #[Test]
  public function multipleJoinResultTest() {
    $rs= new MockResultSet(
      [
        [
          JoinProcessor::FIRST.'_job_id'        => '11',
          JoinProcessor::FIRST.'_title'         => 'clean toilette',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
          JoinProcessor::pathToKey(['PersonJob']).'_person_id'     => '11',
          JoinProcessor::pathToKey(['PersonJob']).'_name'          => 'Schultz',
          JoinProcessor::pathToKey(['PersonJob']).'_job_id'        => '21',
          JoinProcessor::pathToKey(['PersonJob']).'_department_id' => '31',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '11',
          JoinProcessor::FIRST.'_title'         => 'clean toilette',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
          JoinProcessor::pathToKey(['PersonJob']).'_person_id'     => '12',
          JoinProcessor::pathToKey(['PersonJob']).'_name'          => 'Müller',
          JoinProcessor::pathToKey(['PersonJob']).'_job_id'        => '11',
          JoinProcessor::pathToKey(['PersonJob']).'_department_id' => '31',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '12',
          JoinProcessor::FIRST.'_title'         => 'second job',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
          JoinProcessor::pathToKey(['PersonJob']).'_person_id'     => '11',
          JoinProcessor::pathToKey(['PersonJob']).'_name'          => 'Schultz',
          JoinProcessor::pathToKey(['PersonJob']).'_job_id'        => '21',
          JoinProcessor::pathToKey(['PersonJob']).'_department_id' => '31',
        ],
        [
          JoinProcessor::FIRST.'_job_id'        => '13',
          JoinProcessor::FIRST.'_title'         => 'third job',
          JoinProcessor::FIRST.'_valid_from'    => new Date(),
          JoinProcessor::FIRST.'_expire_at'     => '',
          JoinProcessor::pathToKey(['PersonJob']).'_person_id'     => null,
          JoinProcessor::pathToKey(['PersonJob']).'_name'          => null,
          JoinProcessor::pathToKey(['PersonJob']).'_job_id'        => null,
          JoinProcessor::pathToKey(['PersonJob']).'_department_id' => null,
        ],
      ]
    );
    $jp= new JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob' => 'join']);
    $ji= new JoinIterator($jp, $rs);

    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $job= $ji->next());
    Assert::instance('var[]', $job->getPersonJobList());
    Assert::instance(CachedResults::class, $pji= $job->getPersonJobIterator());

    Assert::true($pji->hasNext());
    Assert::instance(Person::class, $pji->next());
    Assert::true($pji->hasNext());
    Assert::instance(Person::class, $pji->next());
    Assert::false($pji->hasNext());

    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $job= $ji->next());
    Assert::instance('var[]', $job->getPersonJobList());
    Assert::instance(CachedResults::class, $pji= $job->getPersonJobIterator());
    Assert::true($pji->hasNext());
    Assert::instance(Person::class, $pji->next());
    Assert::false($pji->hasNext());

    Assert::true($ji->hasNext());
    Assert::instance(Job::class, $job= $ji->next());
    Assert::instance('var[]', $job->getPersonJobList());
    Assert::instance(CachedResults::class, $pji= $job->getPersonJobIterator());
    Assert::false($pji->hasNext());

    Assert::false($ji->hasNext());
  }
}