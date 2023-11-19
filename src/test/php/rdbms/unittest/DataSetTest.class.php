<?php namespace rdbms\unittest;

use lang\{IllegalArgumentException, XPClass};
use rdbms\unittest\dataset\Job;
use rdbms\unittest\mock\{MockConnection, MockResultSet};
use rdbms\{Column, DBEvent, DriverManager, Peer, ResultIterator, SQLException, Statement};
use test\{After, Assert, Before, Expect, Test};
use util\log\BoundLogObserver;
use util\{Date, NoSuchElementException};

class DataSetTest {
  const IRRELEVANT_NUMBER= -1;

  /** @return net.xp_framework.unittest.rdbms.mock.MockConnection */
  protected function getConnection() {
    return Job::getPeer()->getConnection();
  }
  
  /** @param net.xp_framework.unittest.rdbms.mock.MockResultSet $r */
  protected function setResults($r) {
    $this->getConnection()->setResultSet($r);
  }

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
    Job::getPeer()->setConnection(DriverManager::getConnection('mock://mock/JOBS?autoconnect=1'));
  }

  #[Test]
  public function peerObject() {
    $peer= Job::getPeer();
    Assert::instance(Peer::class, $peer);
    Assert::equals('rdbms\unittest\dataset\job', strtolower($peer->identifier));
    Assert::equals('jobs', $peer->connection);
    Assert::equals('JOBS.job', $peer->table);
    Assert::equals('job_id', $peer->identity);
    Assert::equals(
      ['job_id'], 
      $peer->primary
    );
    Assert::equals(
      ['job_id', 'title', 'valid_from', 'expire_at'],
      array_keys($peer->types)
    );
  }
  
  #[Test]
  public function getByJob_id() {
    $now= Date::now();
    $this->setResults(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => 'Unit tester',
        'valid_from'  => $now,
        'expire_at'   => null
      ]
    ]));
    $job= Job::getByJob_id(1);
    Assert::instance(Job::class, $job);
    Assert::equals(1, $job->getJob_id());
    Assert::equals('Unit tester', $job->getTitle());
    Assert::equals($now, $job->getValid_from());
    Assert::null($job->getExpire_at());
  }
  
  #[Test]
  public function newObject() {
    $j= new Job();
    Assert::true($j->isNew());
  }

  #[Test]
  public function existingObject() {
    $this->setResults(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => 'Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));
    
    $job= Job::getByJob_id(1);
    Assert::notEquals(null, $job);
    Assert::false($job->isNew());
  }

  #[Test]
  public function noLongerNewAfterSave() {
    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);
    
    Assert::true($j->isNew());
    $j->save();
    Assert::false($j->isNew());
  }

  #[Test]
  public function noResultsDuringGetByJob_id() {
    $this->setResults(new MockResultSet());
    Assert::null(Job::getByJob_id(self::IRRELEVANT_NUMBER));
  }

  #[Test, Expect(SQLException::class)]
  public function failedQueryInGetByJob_id() {
    $mock= $this->getConnection();
    $mock->makeQueryFail(1, 'Select failed');

    Job::getByJob_id(self::IRRELEVANT_NUMBER);
  }

  #[Test]
  public function insertReturnsIdentity() {
    $mock= $this->getConnection();
    $mock->setIdentityValue(14121977);

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $id= $j->insert();
    Assert::equals(14121977, $id);
  }
  
  #[Test]
  public function saveReturnsIdentityForInserts() {
    $mock= $this->getConnection();
    $mock->setIdentityValue(14121977);

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $id= $j->save();
    Assert::equals(14121977, $id);
  }

  #[Test]
  public function saveReturnsIdentityForUpdates() {
    $this->setResults(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => 'Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));
    
    $job= Job::getByJob_id(1);
    Assert::notEquals(null, $job);
    $id= $job->save();
    Assert::equals(1, $id);
  }
  
  #[Test]
  public function identityFieldIsSet() {
    $mock= $this->getConnection();
    $mock->setIdentityValue(14121977);

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    Assert::equals(0, $j->getJob_id());

    $j->insert();
    Assert::equals(14121977, $j->getJob_id());
  }
  
  #[Test, Expect(SQLException::class)]
  public function failedQueryInInsert() {
    $mock= $this->getConnection();
    $mock->makeQueryFail(1205, 'Deadlock');

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $j->insert();
  }
  
  #[Test]
  public function oneResultForDoSelect() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 1,
        'title'       => 'Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));
  
    $peer= Job::getPeer();
    $jobs= $peer->doSelect(new \rdbms\Criteria(['title', 'Unit tester', EQUAL]));

    Assert::instance('var[]', $jobs);
    Assert::equals(1, sizeof($jobs));
    Assert::instance(Job::class, $jobs[0]);
  }

  #[Test]
  public function noResultForDoSelect() {
    $this->setResults(new MockResultSet());
  
    $peer= Job::getPeer();
    $jobs= $peer->doSelect(new \rdbms\Criteria(['job_id', self::IRRELEVANT_NUMBER, EQUAL]));

    Assert::instance('var[]', $jobs);
    Assert::equals(0, sizeof($jobs));
  }

  #[Test]
  public function multipleResultForDoSelect() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 1,
        'title'       => 'Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ],
      1 => [
        'job_id'      => 9,
        'title'       => 'PHP programmer',
        'valid_from'  => Date::now(),
        'expire_at'   => new Date(time() + 86400 * 7),
      ]
    ]));
  
    $peer= Job::getPeer();
    $jobs= $peer->doSelect(new \rdbms\Criteria(['job_id', 10, LESS_THAN]));

    Assert::instance('var[]', $jobs);
    Assert::equals(2, sizeof($jobs));
    Assert::instance(Job::class, $jobs[0]);
    Assert::equals(1, $jobs[0]->getJob_id());
    Assert::instance(Job::class, $jobs[1]);
    Assert::equals(9, $jobs[1]->getJob_id());
  }
  
  #[Test]
  public function iterateOverCriteria() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ],
      1 => [
        'job_id'      => 329,
        'title'       => 'C# programmer',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));

    $peer= Job::getPeer();
    $iterator= $peer->iteratorFor(new \rdbms\Criteria(['expire_at', null, EQUAL]));

    Assert::instance(ResultIterator::class, $iterator);
    
    // Make sure hasNext() does not forward the resultset pointer
    Assert::true($iterator->hasNext());
    Assert::true($iterator->hasNext());
    Assert::true($iterator->hasNext());
    
    $job= $iterator->next();
    Assert::instance(Job::class, $job);
    Assert::equals(654, $job->getJob_id());

    Assert::true($iterator->hasNext());

    $job= $iterator->next();
    Assert::instance(Job::class, $job);
    Assert::equals(329, $job->getJob_id());

    Assert::false($iterator->hasNext());
  }

  #[Test]
  public function nextCallWithoutHasNext() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ],
      1 => [
        'job_id'      => 329,
        'title'       => 'C# programmer',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));

    $peer= Job::getPeer();
    $iterator= $peer->iteratorFor(new \rdbms\Criteria(['expire_at', null, EQUAL]));

    $job= $iterator->next();
    Assert::instance(Job::class, $job);
    Assert::equals(654, $job->getJob_id());

    Assert::true($iterator->hasNext());
  }

  #[Test, Expect(NoSuchElementException::class)]
  public function nextCallOnEmptyResultSet() {
    $this->setResults(new MockResultSet());
    $peer= Job::getPeer();
    $iterator= $peer->iteratorFor(new \rdbms\Criteria(['expire_at', null, EQUAL]));
    $iterator->next();
  }

  #[Test, Expect(NoSuchElementException::class)]
  public function nextCallPastEndOfResultSet() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));

    $peer= Job::getPeer();
    $iterator= $peer->iteratorFor(new \rdbms\Criteria(['expire_at', null, EQUAL]));
    $iterator->next();
    $iterator->next();
  }
  
  #[Test]
  public function iterateOverStatement() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));

    $peer= Job::getPeer();
    $iterator= $peer->iteratorFor(new Statement('select object(j) from job j where 1 = 1'));
    Assert::instance(ResultIterator::class, $iterator);

    Assert::true($iterator->hasNext());

    $job= $iterator->next();
    Assert::instance(Job::class, $job);
    Assert::equals(654, $job->getJob_id());
    Assert::equals('Java Unit tester', $job->getTitle());

    Assert::false($iterator->hasNext());
  }

  #[Test]
  public function updateUnchangedObject() {

    // First, retrieve an object
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));
    $job= Job::getByJob_id(1);
    Assert::notEquals(null, $job);

    // Second, update the job. Make the next query fail on this 
    // connection to ensure that nothing is actually done.
    $mock= $this->getConnection();
    $mock->makeQueryFail(1326, 'Syntax error');
    $job->update();

    // Make next query return empty results (not fail)
    $this->setResults(new MockResultSet());
  }

  #[Test]
  public function column() {
    $c= Job::column('job_id');
    Assert::instance(Column::class, $c);
    Assert::equals('job_id', $c->getName());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function nonExistantColumn() {
    Job::column('non_existant');
  }

  #[Test]
  public function relativeColumn() {
    Assert::instance(Column::class, Job::column('PersonJob->person_id'));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function nonExistantRelativeColumn() {
    Job::column('PersonJob->non_existant');
  }

  #[Test]
  public function farRelativeColumn() {
    Assert::instance(Column::class, Job::column('PersonJob->Department->department_id'));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function nonExistantfarRelativeColumn() {
    Job::column('PersonJob->Department->non_existant');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function nonExistantRelative() {
    Job::column('NonExistant->person_id');
  }


  #[Test]
  public function doUpdate() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));
    $job= Job::getByJob_id(654);
    Assert::notEquals(null, $job);
    $job->setTitle('PHP Unit tester');
    $job->doUpdate(new \rdbms\Criteria(['job_id', $job->getJob_id(), EQUAL]));
  }

  #[Test]
  public function doDelete() {
    $this->setResults(new MockResultSet([
      0 => [
        'job_id'      => 654,
        'title'       => 'Java Unit tester',
        'valid_from'  => Date::now(),
        'expire_at'   => null
      ]
    ]));
    $job= Job::getByJob_id(654);
    Assert::notEquals(null, $job);
    $job->doDelete(new \rdbms\Criteria(['job_id', $job->getJob_id(), EQUAL]));
  }

  #[Test]
  public function percentSign() {
    $observer= $this->getConnection()->addObserver(new class() implements BoundLogObserver {
      public $statements= [];
      public static function instanceFor($arg) { /* NOOP */ }
      public function update($observable, $event= null) {
        if ($event instanceof DBEvent && 'query' === $event->getName()) {
          $this->statements[]= $event->getArgument();
        }
      }
    });
    $j= new Job();
    $j->setTitle('Percent%20Sign');
    $j->insert();
    
    Assert::equals(
      'insert into JOBS.job (title) values ("Percent%20Sign")',
      $observer->statements[0]
    );
  }

  #[Test]
  public function testDoSelectMax() {
    for ($i= 0; $i < 4; $i++) {
      $this->setResults(new MockResultSet([
        0 => [
          'job_id'      => 654,
          'title'       => 'Java Unit tester',
          'valid_from'  => Date::now(),
          'expire_at'   => null
        ],
        1 => [
          'job_id'      => 655,
          'title'       => 'Java Unit tester 1',
          'valid_from'  => Date::now(),
          'expire_at'   => null
        ],
        2 => [
          'job_id'      => 656,
          'title'       => 'Java Unit tester 2',
          'valid_from'  => Date::now(),
          'expire_at'   => null
        ],
        3 => [
          'job_id'      => 657,
          'title'       => 'Java Unit tester 3',
          'valid_from'  => Date::now(),
          'expire_at'   => null
        ],
      ]));
      Assert::equals($i ? $i : 4, count(Job::getPeer()->doSelect(new \rdbms\Criteria(), $i)));
    }
  }
}