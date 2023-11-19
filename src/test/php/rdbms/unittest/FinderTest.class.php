<?php namespace rdbms\unittest;

use lang\{IllegalArgumentException, MethodNotImplementedException, XPClass};
use rdbms\finder\{FinderException, FinderMethod, GenericFinder, NoSuchEntityException};
use rdbms\unittest\dataset\{Job, JobFinder};
use rdbms\unittest\mock\{MockConnection, MockResultSet};
use rdbms\{DriverManager, Peer, SQLExpression};
use test\{Assert, Before, After, Expect, Test};

class FinderTest {
  protected $fixture;

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
    $this->fixture= new JobFinder();
    $this->fixture->getPeer()->setConnection(DriverManager::getConnection('mock://mock/JOBS?autoconnect=1'));
  }

  /**
   * Helper method which invokes the finder's method() method and un-wraps
   * exceptions thrown.
   *
   * @param   string $name
   * @return  rdbms.finder.FinderMethod
   * @throws  lang.Throwable
   */
  protected function method($name) {
    try {
      return $this->fixture->method($name);
    } catch (FinderException $e) {
      throw $e->getCause();
    }
  }

  /**
   * Helper methods
   *
   * @return  net.xp_framework.unittest.rdbms.mock.MockConnection
   */
  protected function getConnection() {
    return $this->fixture->getPeer()->getConnection();
  }

  #[Test]
  public function peerObject() {
    Assert::instance(Peer::class, $this->fixture->getPeer());
  }

  #[Test]
  public function jobPeer() {
    Assert::equals($this->fixture->getPeer(), Job::getPeer());
  }

  #[Test]
  public function entityMethods() {
    $methods= $this->fixture->entityMethods();
    Assert::equals(1, sizeof($methods));
    Assert::instance(FinderMethod::class, $methods[0]);
    Assert::equals(ENTITY, $methods[0]->getKind());
    Assert::equals('byPrimary', $methods[0]->getName());
    Assert::instance(SQLExpression::class, $methods[0]->invoke([$pk= 1]));
  }

  #[Test]
  public function collectionMethods() {
    static $invocation= [
      'all'         => [],
      'newestJobs'  => [],
      'expiredJobs' => [],
      'similarTo'   => ['Test']
    ];

    $methods= $this->fixture->collectionMethods();
    Assert::equals(4, sizeof($methods)); // three declared plu all()
    foreach ($methods as $method) {
      Assert::instance(FinderMethod::class, $method);
      $name= $method->getName();
      Assert::equals(COLLECTION, $method->getKind(), $name);
      Assert::equals(true, isset($invocation[$name]), $name);
      Assert::instance(SQLExpression::class, $method->invoke($invocation[$name]), $name);
    }
  }

  #[Test]
  public function allMethods() {
    $methods= $this->fixture->allMethods(); // four declared plu all()
    Assert::equals(5, sizeof($methods));
  }

  #[Test]
  public function byPrimaryMethod() {
    $method= $this->fixture->method('byPrimary');
    Assert::instance(FinderMethod::class, $method);
    Assert::equals('byPrimary', $method->getName());
    Assert::equals(ENTITY, $method->getKind());
  }
  
  #[Test, Expect(MethodNotImplementedException::class)]
  public function nonExistantMethod() {
    $this->method('@@NON-EXISTANT@@');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function notAFinderMethod() {
    $this->method('getPeer');
  }
  
  #[Test]
  public function findByExistingPrimary() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->find($this->fixture->byPrimary(1));
    Assert::instance(Job::class, $entity);
  }

  #[Test]
  public function findByExistingPrimaryFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->find()->byPrimary(1);
    Assert::instance(Job::class, $entity);
  }

  #[Test]
  public function findByNonExistantPrimary() {
    Assert::null($this->fixture->find($this->fixture->byPrimary(0)));
  }

  #[Test, Expect(FinderException::class)]
  public function findUnexpectedResults() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $this->fixture->find($this->fixture->byPrimary(1));
  }

  #[Test]
  public function getByExistingPrimary() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->get($this->fixture->byPrimary(1));
    Assert::instance(Job::class, $entity);
  }

  #[Test]
  public function getByExistingPrimaryFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->get()->byPrimary(1);
    Assert::instance(Job::class, $entity);
  }

  #[Test, Expect(NoSuchEntityException::class)]
  public function getByNonExistantPrimary() {
    $this->fixture->get($this->fixture->byPrimary(0));
  }

  #[Test, Expect(FinderException::class)]
  public function getUnexpectedResults() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $this->fixture->get($this->fixture->byPrimary(1));
  }

  #[Test]
  public function findNewestJobs() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->findAll($this->fixture->newestJobs());
    Assert::equals(2, sizeof($collection));
  }

  #[Test]
  public function findNewestJobsFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->findAll()->newestJobs();
    Assert::equals(2, sizeof($collection));
  }

  #[Test]
  public function getNewestJobs() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->getAll($this->fixture->newestJobs());
    Assert::equals(2, sizeof($collection));
  }

  #[Test]
  public function getNewestJobsFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->getAll()->newestJobs();
    Assert::equals(2, sizeof($collection));
  }

  #[Test, Expect(NoSuchEntityException::class)]
  public function getNothingFound() {
    $this->fixture->getAll($this->fixture->newestJobs());
  }

  #[Test, Expect(FinderException::class)]
  public function findWrapsSQLException() {
    $this->getConnection()->makeQueryFail(6010, 'Not enough power');
    $this->fixture->find(new \rdbms\Criteria());
  }

  #[Test, Expect(FinderException::class)]
  public function findAllWrapsSQLException() {
    $this->getConnection()->makeQueryFail(6010, 'Not enough power');
    $this->fixture->findAll(new \rdbms\Criteria());
  }

  #[Test, Expect(class: FinderException::class, message: '/No such method nonExistantMethod/')]
  public function fluentNonExistantFinder() {
    $this->fixture->findAll()->nonExistantMethod(new \rdbms\Criteria());
  }

  #[Test]
  public function genericFinderGetAll() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => __METHOD__,
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => __METHOD__.' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $all= (new GenericFinder(Job::getPeer()))->getAll(new \rdbms\Criteria());
    Assert::equals(2, sizeof($all));
    Assert::instance(Job::class, $all[0]);
    Assert::instance(Job::class, $all[1]);
  }
}