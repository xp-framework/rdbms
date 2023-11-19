<?php namespace rdbms\unittest;

use lang\XPClass;
use rdbms\query\{DeleteQuery, SelectQuery, SetOperation, UpdateQuery};
use rdbms\unittest\dataset\{Job, Person};
use rdbms\unittest\mock\MockConnection;
use rdbms\{Criteria, DriverManager};
use unittest\{Assert, Before, After, Test};

class QueryTest {
  private
    $qa= null,
    $qb= null,
    $qas= 'select  job_id, title from JOBS.job  where job_id = 5',
    $qbs= 'select  job_id, name from JOBS.Person ',
    $qu= null;

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
    with ($conn= \rdbms\DriverManager::getConnection('mock://mock/JOBS?autoconnect=1')); {
      Job::getPeer()->setConnection($conn);
      Person::getPeer()->setConnection($conn);
    }

    $this->qa= new SelectQuery();
    $this->qa->setPeer(Job::getPeer());
    $this->qa->setCriteria(
      (new Criteria(Job::column('job_id')->equal(5)))->setProjection(
        \rdbms\criterion\Projections::ProjectionList()
        ->add(Job::column('job_id'))
        ->add(Job::column('title'))
      )
    );

    $this->qb= new SelectQuery();
    $this->qb->setPeer(Person::getPeer());
    $this->qb->setCriteria(
      (new Criteria())->setProjection(
        \rdbms\criterion\Projections::ProjectionList()
        ->add(Person::column('job_id'))
        ->add(Person::column('name'))
      )
    );
  }
  
  #[Test]
  public function setCriteria() {
    $q= new SelectQuery();
    $c= new Criteria();
    $q->setCriteria($c);
    Assert::equals($c, $q->getCriteria());
  }
  
  #[Test]
  public function setPeer() {
    $q= new SelectQuery();
    $q->setPeer(Job::getPeer());
    Assert::equals(Job::getPeer(), $q->getPeer());
  }
  
  #[Test]
  public function getConnection() {
    $q= new SelectQuery();
    Assert::null($q->getConnection());
    $q->setPeer(Job::getPeer());
    Assert::instance(MockConnection::class, $q->getConnection());
  }
  
  #[Test]
  public function executeWithRestriction() {
    Assert::instance(SelectQuery::class, (new SelectQuery())->withRestriction(Job::column('job_id')->equal(5)));
  }
  
  #[Test]
  public function getSingleQueryString() {
    Assert::equals($this->qas, $this->qa->getQueryString());
    Assert::equals($this->qbs, $this->qb->getQueryString());
  }
  
  #[Test]
  public function getQueryString() {
    $so= new SetOperation(SetOperation::UNION, $this->qa, $this->qb);
    Assert::equals(
      $this->qas.' union '.$this->qbs,
      $so->getQueryString()
    );
  }
  
  #[Test]
  public function factory() {
    $so= SetOperation::union($this->qa, $this->qb);
    Assert::equals(
      $this->qas.' union '.$this->qbs,
      $so->getQueryString()
    );
    $so= SetOperation::except($this->qa, $this->qb);
    Assert::equals(
      $this->qas.' except '.$this->qbs,
      $so->getQueryString()
    );
    $so= SetOperation::intercept($this->qa, $this->qb);
    Assert::equals(
      $this->qas.' intercept '.$this->qbs,
      $so->getQueryString()
    );
  }
  
  #[Test]
  public function all() {
    $so= SetOperation::union($this->qa, $this->qb, true);
    Assert::equals(
      $this->qas.' union all '.$this->qbs,
      $so->getQueryString()
    );
  }
  
  #[Test]
  public function nesting() {
    $so= SetOperation::union(SetOperation::union($this->qb, $this->qa), SetOperation::union($this->qb, $this->qa));
    Assert::equals(
      $this->qbs.' union '.$this->qas.' union '.$this->qbs.' union '.$this->qas,
      $so->getQueryString()
    );
  }
  
}