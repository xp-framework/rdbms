<?php namespace rdbms\unittest;

use lang\{IllegalArgumentException, XPClass};
use rdbms\criterion\Restrictions;
use rdbms\unittest\dataset\Job;
use rdbms\unittest\mock\MockConnection;
use rdbms\{Criteria, DriverManager, SQLStateException};
use test\{After, Assert, Before, Expect, Test};

class CriteriaTest {
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
   * Helper method that will call toSQL() on the passed criteria and
   * compare the resulting string to the expected string.
   *
   * @param   string sql
   * @param   rdbms.Criteria criteria
   * @throws  unittest.AssertionFailedError
   */
  protected function assertSql($sql, $criteria) {
    Assert::equals($sql, trim($criteria->toSQL($this->conn, $this->peer), ' '));
  }

  #[Test]
  public function emptyCriteria() {
    $this->assertSql('', new Criteria());
  }

  #[Test]
  public function simpleCriteria() {
    $this->assertSql('where job_id = 1', new Criteria(['job_id', 1, EQUAL]));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function nonExistantFieldCausesException() {
    $criteria= new Criteria(['non-existant-field', 1, EQUAL]);
    $criteria->toSQL($this->conn, $this->peer);
  }

  #[Test]
  public function complexCriteria() {
    with ($c= new Criteria()); {
      $c->add('job_id', 1, EQUAL);
      $c->add('valid_from', new \util\Date('2006-01-01'), GREATER_EQUAL);
      $c->add('title', 'Hello%', LIKE);
      $c->addOrderBy('valid_from');
    }

    $this->assertSql(
      'where job_id = 1 and valid_from >= "2006-01-01 12:00AM" and title like "Hello%" order by valid_from asc', 
      $c
    );
  }

  #[Test]
  public function inCriteria() {
    $c= new Criteria();
    $c->add('job_id', [1, 2], IN);
    
    $this->assertSql('where job_id in (1, 2)', $c);
  }
  
  #[Test]
  public function notInCriteria() {
    $c= new Criteria();
    $c->add('job_id', [1, 2], NOT_IN);
    
    $this->assertSql('where job_id not in (1, 2)', $c);
  }
  
  #[Test]
  public function likeCriteria() {
    $c= new Criteria();
    $c->add('title', '%keyword%', LIKE);
    
    $this->assertSql('where title like "%keyword%"', $c);
  }
  
  #[Test]
  public function equalCriteria() {
    $c= new Criteria();
    $c->add('job_id', 1, EQUAL);
    
    $this->assertSql('where job_id = 1', $c);
  }
  
  #[Test]
  public function notEqualCriteria() {
    $c= new Criteria();
    $c->add('job_id', 1, NOT_EQUAL);
    
    $this->assertSql('where job_id != 1', $c);
  }
  
  #[Test]
  public function lessThanCriteria() {
    $c= new Criteria();
    $c->add('job_id', 100, LESS_THAN);
    
    $this->assertSql('where job_id < 100', $c);
  }
  
  #[Test]
  public function greaterThanCriteria() {
    $c= new Criteria();
    $c->add('job_id', 100, GREATER_THAN);
    
    $this->assertSql('where job_id > 100', $c);
  }
  
  #[Test]
  public function lessEqualCriteria() {
    $c= new Criteria();
    $c->add('job_id', 100, LESS_EQUAL);
    
    $this->assertSql('where job_id <= 100', $c);
  }
  
  #[Test]
  public function greaterEqualCriteria() {
    $c= new Criteria();
    $c->add('job_id', 100, GREATER_EQUAL);
    
    $this->assertSql('where job_id >= 100', $c);
  }
  
  #[Test]
  public function bitAndCriteria() {
    $c= new Criteria();
    $c->add('job_id', 100, BIT_AND);
    
    $this->assertSql('where job_id & 100 != 0', $c);
  }
  
  #[Test]
  public function restrictionsFactory() {
    $job_id= Job::column('job_id');
    $c= new Criteria(Restrictions::anyOf(
      Restrictions::not($job_id->in([1, 2, 3])),
      Restrictions::allOf(
        Job::column('title')->like('Hello%'),
        Job::column('valid_from')->greaterThan(new \util\Date('2006-01-01'))
      ),
      Restrictions::allOf(
        Restrictions::like('title', 'Hello%'),
        Restrictions::greaterThan('valid_from', new \util\Date('2006-01-01'))
      ),
      $job_id->between(1, 5)
    ));

    $this->assertSql(
      'where (not (job_id in (1, 2, 3))'.
      ' or (title like "Hello%" and valid_from > "2006-01-01 12:00AM")'.
      ' or (title like "Hello%" and valid_from > "2006-01-01 12:00AM")'.
      ' or job_id between 1 and 5)',
      $c
    );
  }
  
  #[Test]
  public function constructorAcceptsVarArgArrays() {
    $this->assertSql(
      'where job_id = 1 and title = "Hello"', 
      new Criteria(['job_id', 1, EQUAL], ['title', 'Hello', EQUAL])
    );
  }

  #[Test]
  public function addReturnsThis() {
    Assert::instance(Criteria::class, (new Criteria())->add('job_id', 1, EQUAL));
  }

  #[Test]
  public function addOrderByReturnsThis() {
    Assert::instance(Criteria::class, (new Criteria())->add('job_id', 1, EQUAL)->addOrderBy('valid_from', DESCENDING));
  }

  #[Test]
  public function addGroupByReturnsThis() {
    Assert::instance(Criteria::class, (new Criteria())->add('job_id', 1, EQUAL)->addGroupBy('valid_from'));
  }

  #[Test]
  public function addOrderByColumn() {
    with ($c= new Criteria()); {
      $c->addOrderBy(Job::column('valid_from'));
      $c->addOrderBy(Job::column('expire_at'));
    }
    $this->assertSql(
      'order by valid_from asc, expire_at asc',
      $c
    );
  }

  #[Test]
  public function addOrderByString() {
    with ($c= new Criteria()); {
      $c->addOrderBy("valid_from");
      $c->addOrderBy("expire_at");
    }
    $this->assertSql(
      'order by valid_from asc, expire_at asc',
      $c
    );
  }

  #[Test]
  public function addGroupByColumn() {
    with ($c= new Criteria()); {
      $c->addGroupBy(Job::column('valid_from'));
      $c->addGroupBy(Job::column('expire_at'));
    }
    $this->assertSql(
      'group by valid_from, expire_at',
      $c
    );
  }

  #[Test]
  public function addGroupByString() {
    with ($c= new Criteria()); {
      $c->addGroupBy("valid_from");
      $c->addGroupBy("expire_at");
    }
    $this->assertSql(
      'group by valid_from, expire_at',
      $c
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function createNonExistantColumn() {
    Job::column('not_existant');
  }

  #[Test, Expect(SQLStateException::class)]
  public function addGroupByNonExistantColumnString() {
    (new Criteria())->addGroupBy('not_existant')->toSQL($this->conn, $this->peer);
  }

  #[Test]
  public function fetchModeChaining() {
    Assert::instance(Criteria::class, (new Criteria())->setFetchmode(\rdbms\join\Fetchmode::join('PersonJob')));
  }

  #[Test]
  public function testIsJoin() {
    $crit= new Criteria();
    Assert::false($crit->isJoin());
    Assert::true($crit->setFetchmode(\rdbms\join\Fetchmode::join('PersonJob'))->isJoin());
    $crit->fetchmode= [];
    Assert::false($crit->isJoin());
    Assert::false($crit->setFetchmode(\rdbms\join\Fetchmode::select('PersonJob'))->isJoin());
  }

  #[Test]
  public function testJoinWithoutCondition() {
    $jp= new \rdbms\join\JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    $jp->enterJoinContext();
    Assert::equals(
      '1 = 1',
      (new Criteria())
      ->setFetchmode(\rdbms\join\Fetchmode::join('PersonJob'))
      ->toSQL($this->conn, $this->peer)
    );
    $jp->leaveJoinContext();
  }

  #[Test]
  public function testJoinWithCondition() {
    $jp= new \rdbms\join\JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    $jp->enterJoinContext();
    Assert::equals(
      'PersonJob_Department.department_id = 5 and start.job_id = 2',
      (new Criteria())
        ->setFetchmode(\rdbms\join\Fetchmode::join('PersonJob'))
        ->add(Job::column('PersonJob->Department->department_id')->equal(5))
        ->add(Job::column('job_id')->equal(2))
        ->toSQL($this->conn, $this->peer)
    );
    $jp->leaveJoinContext();
  }

  #[Test]
  public function testJoinWithProjection() {
    $jp= new \rdbms\join\JoinProcessor(Job::getPeer());
    $jp->setFetchModes(['PersonJob->Department' => 'join']);
    $jp->enterJoinContext();
    try {
      Assert::equals(
        'select  PersonJob.job_id, PersonJob_Department.department_id from JOBS.job as start, JOBS.Person as PersonJob, JOBS.Department as PersonJob_Department where start.job_id *= PersonJob.job_id and PersonJob.department_id *= PersonJob_Department.department_id and  1 = 1',
        (new Criteria())
          ->setFetchmode(\rdbms\join\Fetchmode::join('PersonJob'))
          ->setProjection(\rdbms\criterion\Projections::projectionList()
            ->add(Job::column('PersonJob->job_id'))
            ->add(Job::column('PersonJob->Department->department_id'))
          )
          ->getSelectQueryString($this->conn, $this->peer, $jp)
      );
    } finally {
      $jp->leaveJoinContext();
    }
  }
}