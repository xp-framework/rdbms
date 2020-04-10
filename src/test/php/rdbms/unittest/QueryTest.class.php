<?php namespace rdbms\unittest;
 
use rdbms\Criteria;
use rdbms\query\{DeleteQuery, SelectQuery, SetOperation, UpdateQuery};
use rdbms\unittest\dataset\{Job, Person};
use rdbms\unittest\mock\MockConnection;
use unittest\TestCase;

/**
 * Test query class
 *
 * @see  xp://rdbms.Query
 */
#[@action(new \rdbms\unittest\mock\RegisterMockConnection())]
class QueryTest extends TestCase {
  private
    $qa= null,
    $qb= null,
    $qas= 'select  job_id, title from JOBS.job  where job_id = 5',
    $qbs= 'select  job_id, name from JOBS.Person ',
    $qu= null;
    
  /**
   * Setup method
   */
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
  
  #[@test]
  public function setCriteria() {
    $q= new SelectQuery();
    $c= new Criteria();
    $q->setCriteria($c);
    $this->assertEquals($c, $q->getCriteria());
  }
  
  #[@test]
  public function setPeer() {
    $q= new SelectQuery();
    $q->setPeer(Job::getPeer());
    $this->assertEquals(Job::getPeer(), $q->getPeer());
  }
  
  #[@test]
  public function getConnection() {
    $q= new SelectQuery();
    $this->assertNull($q->getConnection());
    $q->setPeer(Job::getPeer());
    $this->assertInstanceOf(MockConnection::class, $q->getConnection());
  }
  
  #[@test]
  public function executeWithRestriction() {
    $this->assertInstanceOf(SelectQuery::class, (new SelectQuery())->withRestriction(Job::column('job_id')->equal(5)));
  }
  
  #[@test]
  public function getSingleQueryString() {
    $this->assertEquals($this->qas, $this->qa->getQueryString());
    $this->assertEquals($this->qbs, $this->qb->getQueryString());
  }
  
  #[@test]
  public function getQueryString() {
    $so= new SetOperation(SetOperation::UNION, $this->qa, $this->qb);
    $this->assertEquals(
      $this->qas.' union '.$this->qbs,
      $so->getQueryString()
    );
  }
  
  #[@test]
  public function factory() {
    $so= SetOperation::union($this->qa, $this->qb);
    $this->assertEquals(
      $this->qas.' union '.$this->qbs,
      $so->getQueryString()
    );
    $so= SetOperation::except($this->qa, $this->qb);
    $this->assertEquals(
      $this->qas.' except '.$this->qbs,
      $so->getQueryString()
    );
    $so= SetOperation::intercept($this->qa, $this->qb);
    $this->assertEquals(
      $this->qas.' intercept '.$this->qbs,
      $so->getQueryString()
    );
  }
  
  #[@test]
  public function all() {
    $so= SetOperation::union($this->qa, $this->qb, true);
    $this->assertEquals(
      $this->qas.' union all '.$this->qbs,
      $so->getQueryString()
    );
  }
  
  #[@test]
  public function nesting() {
    $so= SetOperation::union(SetOperation::union($this->qb, $this->qa), SetOperation::union($this->qb, $this->qa));
    $this->assertEquals(
      $this->qbs.' union '.$this->qas.' union '.$this->qbs.' union '.$this->qas,
      $so->getQueryString()
    );
  }
  
}