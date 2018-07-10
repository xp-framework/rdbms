<?php namespace rdbms\unittest;

use lang\IllegalArgumentException;
use rdbms\Column;
use rdbms\DBObserver;
use rdbms\DriverManager;
use rdbms\Peer;
use rdbms\ResultIterator;
use rdbms\SQLException;
use rdbms\Statement;
use rdbms\unittest\dataset\Job;
use rdbms\unittest\mock\MockResultSet;
use unittest\TestCase;
use util\Date;
use util\DateUtil;
use util\NoSuchElementException;

/**
 * O/R-mapping API unit test
 *
 * @see      xp://rdbms.DataSet
 */
#[@action(new \rdbms\unittest\mock\RegisterMockConnection())]
class DataSetTest extends TestCase {
  const IRRELEVANT_NUMBER= -1;

  /**
   * Setup method
   */
  public function setUp() {
    Job::getPeer()->setConnection(DriverManager::getConnection('mock://mock/JOBS', false));
  }
  
  /**
   * Helper methods
   *
   * @return  net.xp_framework.unittest.rdbms.mock.MockConnection
   */
  protected function getConnection() {
    return Job::getPeer()->getConnection();
  }
  
  /**
   * Helper method
   *
   * @param   net.xp_framework.unittest.rdbms.mock.MockResultSet r
   */
  protected function setResults($r) {
    $this->getConnection()->setResultSet($r);
  }
  
  #[@test]
  public function peerObject() {
    $peer= Job::getPeer();
    $this->assertInstanceOf(Peer::class, $peer);
    $this->assertEquals('rdbms\unittest\dataset\job', strtolower($peer->identifier));
    $this->assertEquals('jobs', $peer->connection);
    $this->assertEquals('JOBS.job', $peer->table);
    $this->assertEquals('job_id', $peer->identity);
    $this->assertEquals(
      ['job_id'], 
      $peer->primary
    );
    $this->assertEquals(
      ['job_id', 'title', 'valid_from', 'expire_at'],
      array_keys($peer->types)
    );
  }
  
  #[@test]
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
    $this->assertInstanceOf(Job::class, $job);
    $this->assertEquals(1, $job->getJob_id());
    $this->assertEquals('Unit tester', $job->getTitle());
    $this->assertEquals($now, $job->getValid_from());
    $this->assertNull($job->getExpire_at());
  }
  
  #[@test]
  public function newObject() {
    $j= new Job();
    $this->assertTrue($j->isNew());
  }

  #[@test]
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
    $this->assertNotEquals(null, $job);
    $this->assertFalse($job->isNew());
  }

  #[@test]
  public function noLongerNewAfterSave() {
    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);
    
    $this->assertTrue($j->isNew());
    $j->save();
    $this->assertFalse($j->isNew());
  }

  #[@test]
  public function noResultsDuringGetByJob_id() {
    $this->setResults(new MockResultSet());
    $this->assertNull(Job::getByJob_id(self::IRRELEVANT_NUMBER));
  }

  #[@test, @expect(SQLException::class)]
  public function failedQueryInGetByJob_id() {
    $mock= $this->getConnection();
    $mock->makeQueryFail(1, 'Select failed');

    Job::getByJob_id(self::IRRELEVANT_NUMBER);
  }

  #[@test]
  public function insertReturnsIdentity() {
    $mock= $this->getConnection();
    $mock->setIdentityValue(14121977);

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $id= $j->insert();
    $this->assertEquals(14121977, $id);
  }
  
  #[@test]
  public function saveReturnsIdentityForInserts() {
    $mock= $this->getConnection();
    $mock->setIdentityValue(14121977);

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $id= $j->save();
    $this->assertEquals(14121977, $id);
  }

  #[@test]
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
    $this->assertNotEquals(null, $job);
    $id= $job->save();
    $this->assertEquals(1, $id);
  }
  
  #[@test]
  public function identityFieldIsSet() {
    $mock= $this->getConnection();
    $mock->setIdentityValue(14121977);

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $this->assertEquals(0, $j->getJob_id());

    $j->insert();
    $this->assertEquals(14121977, $j->getJob_id());
  }
  
  #[@test, @expect(SQLException::class)]
  public function failedQueryInInsert() {
    $mock= $this->getConnection();
    $mock->makeQueryFail(1205, 'Deadlock');

    $j= new Job();
    $j->setTitle('New job');
    $j->setValid_from(Date::now());
    $j->setExpire_at(null);

    $j->insert();
  }
  
  #[@test]
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

    $this->assertInstanceOf('var[]', $jobs);
    $this->assertEquals(1, sizeof($jobs));
    $this->assertInstanceOf(Job::class, $jobs[0]);
  }

  #[@test]
  public function noResultForDoSelect() {
    $this->setResults(new MockResultSet());
  
    $peer= Job::getPeer();
    $jobs= $peer->doSelect(new \rdbms\Criteria(['job_id', self::IRRELEVANT_NUMBER, EQUAL]));

    $this->assertInstanceOf('var[]', $jobs);
    $this->assertEquals(0, sizeof($jobs));
  }

  #[@test]
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
        'expire_at'   => DateUtil::addDays(Date::now(), 7)
      ]
    ]));
  
    $peer= Job::getPeer();
    $jobs= $peer->doSelect(new \rdbms\Criteria(['job_id', 10, LESS_THAN]));

    $this->assertInstanceOf('var[]', $jobs);
    $this->assertEquals(2, sizeof($jobs));
    $this->assertInstanceOf(Job::class, $jobs[0]);
    $this->assertEquals(1, $jobs[0]->getJob_id());
    $this->assertInstanceOf(Job::class, $jobs[1]);
    $this->assertEquals(9, $jobs[1]->getJob_id());
  }
  
  #[@test]
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

    $this->assertInstanceOf(ResultIterator::class, $iterator);
    
    // Make sure hasNext() does not forward the resultset pointer
    $this->assertTrue($iterator->hasNext());
    $this->assertTrue($iterator->hasNext());
    $this->assertTrue($iterator->hasNext());
    
    $job= $iterator->next();
    $this->assertInstanceOf(Job::class, $job);
    $this->assertEquals(654, $job->getJob_id());

    $this->assertTrue($iterator->hasNext());

    $job= $iterator->next();
    $this->assertInstanceOf(Job::class, $job);
    $this->assertEquals(329, $job->getJob_id());

    $this->assertFalse($iterator->hasNext());
  }

  #[@test]
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
    $this->assertInstanceOf(Job::class, $job);
    $this->assertEquals(654, $job->getJob_id());

    $this->assertTrue($iterator->hasNext());
  }

  #[@test, @expect(NoSuchElementException::class)]
  public function nextCallOnEmptyResultSet() {
    $this->setResults(new MockResultSet());
    $peer= Job::getPeer();
    $iterator= $peer->iteratorFor(new \rdbms\Criteria(['expire_at', null, EQUAL]));
    $iterator->next();
  }

  #[@test, @expect(NoSuchElementException::class)]
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
  
  #[@test]
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
    $this->assertInstanceOf(ResultIterator::class, $iterator);

    $this->assertTrue($iterator->hasNext());

    $job= $iterator->next();
    $this->assertInstanceOf(Job::class, $job);
    $this->assertEquals(654, $job->getJob_id());
    $this->assertEquals('Java Unit tester', $job->getTitle());

    $this->assertFalse($iterator->hasNext());
  }

  #[@test]
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
    $this->assertNotEquals(null, $job);

    // Second, update the job. Make the next query fail on this 
    // connection to ensure that nothing is actually done.
    $mock= $this->getConnection();
    $mock->makeQueryFail(1326, 'Syntax error');
    $job->update();

    // Make next query return empty results (not fail)
    $this->setResults(new MockResultSet());
  }

  #[@test]
  public function column() {
    $c= Job::column('job_id');
    $this->assertInstanceOf(Column::class, $c);
    $this->assertEquals('job_id', $c->getName());
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function nonExistantColumn() {
    Job::column('non_existant');
  }

  #[@test]
  public function relativeColumn() {
    $this->assertInstanceOf(Column::class, Job::column('PersonJob->person_id'));
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function nonExistantRelativeColumn() {
    Job::column('PersonJob->non_existant');
  }

  #[@test]
  public function farRelativeColumn() {
    $this->assertInstanceOf(Column::class, Job::column('PersonJob->Department->department_id'));
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function nonExistantfarRelativeColumn() {
    Job::column('PersonJob->Department->non_existant');
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function nonExistantRelative() {
    Job::column('NonExistant->person_id');
  }


  #[@test]
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
    $this->assertNotEquals(null, $job);
    $job->setTitle('PHP Unit tester');
    $job->doUpdate(new \rdbms\Criteria(['job_id', $job->getJob_id(), EQUAL]));
  }

  #[@test]
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
    $this->assertNotEquals(null, $job);
    $job->doDelete(new \rdbms\Criteria(['job_id', $job->getJob_id(), EQUAL]));
  }

  #[@test]
  public function percentSign() {
    $observer= $this->getConnection()->addObserver(newinstance(DBObserver::class, [], '{
      public $statements= [];
      public static function instanceFor($arg) { }
      public function update($observable, $event= null) {
        if ($event instanceof DBEvent && "query" == $event->getName()) {
          $this->statements[]= $event->getArgument();
        }
      }
    }'));
    $j= new Job();
    $j->setTitle('Percent%20Sign');
    $j->insert();
    
    $this->assertEquals(
      'insert into JOBS.job (title) values ("Percent%20Sign")',
      $observer->statements[0]
    );
  }

  #[@test]
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
      $this->assertEquals($i ? $i : 4, count(Job::getPeer()->doSelect(new \rdbms\Criteria(), $i)));
    }
  }
}
