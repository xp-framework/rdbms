<?php namespace rdbms\unittest;

use lang\{IllegalArgumentException, MethodNotImplementedException};
use rdbms\finder\{FinderException, FinderMethod, GenericFinder, NoSuchEntityException};
use rdbms\unittest\dataset\{Job, JobFinder};
use rdbms\unittest\mock\{MockResultSet, RegisterMockConnection};
use rdbms\{DriverManager, Peer, SQLExpression};
use unittest\{Expect, Test, TestCase};

/**
 * TestCase
 *
 * @see      xp://rdbms.finder.Finder
 */
#[Action(eval: 'new RegisterMockConnection()')]
class FinderTest extends TestCase {
  protected $fixture = null;

  /**
   * Setup method
   */
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
    $this->assertInstanceOf(Peer::class, $this->fixture->getPeer());
  }

  #[Test]
  public function jobPeer() {
    $this->assertEquals($this->fixture->getPeer(), Job::getPeer());
  }

  #[Test]
  public function entityMethods() {
    $methods= $this->fixture->entityMethods();
    $this->assertEquals(1, sizeof($methods));
    $this->assertInstanceOf(FinderMethod::class, $methods[0]);
    $this->assertEquals(ENTITY, $methods[0]->getKind());
    $this->assertEquals('byPrimary', $methods[0]->getName());
    $this->assertInstanceOf(SQLExpression::class, $methods[0]->invoke([$pk= 1]));
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
    $this->assertEquals(4, sizeof($methods)); // three declared plu all()
    foreach ($methods as $method) {
      $this->assertInstanceOf(FinderMethod::class, $method);
      $name= $method->getName();
      $this->assertEquals(COLLECTION, $method->getKind(), $name);
      $this->assertEquals(true, isset($invocation[$name]), $name);
      $this->assertInstanceOf(SQLExpression::class, $method->invoke($invocation[$name]), $name);
    }
  }

  #[Test]
  public function allMethods() {
    $methods= $this->fixture->allMethods(); // four declared plu all()
    $this->assertEquals(5, sizeof($methods));
  }

  #[Test]
  public function byPrimaryMethod() {
    $method= $this->fixture->method('byPrimary');
    $this->assertInstanceOf(FinderMethod::class, $method);
    $this->assertEquals('byPrimary', $method->getName());
    $this->assertEquals(ENTITY, $method->getKind());
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
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->find($this->fixture->byPrimary(1));
    $this->assertInstanceOf(Job::class, $entity);
  }

  #[Test]
  public function findByExistingPrimaryFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->find()->byPrimary(1);
    $this->assertInstanceOf(Job::class, $entity);
  }

  #[Test]
  public function findByNonExistantPrimary() {
    $this->assertNull($this->fixture->find($this->fixture->byPrimary(0)));
  }

  #[Test, Expect(FinderException::class)]
  public function findUnexpectedResults() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
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
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->get($this->fixture->byPrimary(1));
    $this->assertInstanceOf(Job::class, $entity);
  }

  #[Test]
  public function getByExistingPrimaryFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $entity= $this->fixture->get()->byPrimary(1);
    $this->assertInstanceOf(Job::class, $entity);
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
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
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
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->findAll($this->fixture->newestJobs());
    $this->assertEquals(2, sizeof($collection));
  }

  #[Test]
  public function findNewestJobsFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->findAll()->newestJobs();
    $this->assertEquals(2, sizeof($collection));
  }

  #[Test]
  public function getNewestJobs() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->getAll($this->fixture->newestJobs());
    $this->assertEquals(2, sizeof($collection));
  }

  #[Test]
  public function getNewestJobsFluent() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $collection= $this->fixture->getAll()->newestJobs();
    $this->assertEquals(2, sizeof($collection));
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

  #[Test, Expect(['class' => FinderException::class, 'withMessage' => '/No such method nonExistantMethod/'])]
  public function fluentNonExistantFinder() {
    $this->fixture->findAll()->nonExistantMethod(new \rdbms\Criteria());
  }

  #[Test]
  public function genericFinderGetAll() {
    $this->getConnection()->setResultSet(new MockResultSet([
      0 => [   // First row
        'job_id'      => 1,
        'title'       => $this->getName(),
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ],
      1 => [   // Second row
        'job_id'      => 2,
        'title'       => $this->getName().' #2',
        'valid_from'  => \util\Date::now(),
        'expire_at'   => null
      ]
    ]));
    $all= (new GenericFinder(Job::getPeer()))->getAll(new \rdbms\Criteria());
    $this->assertEquals(2, sizeof($all));
    $this->assertInstanceOf(Job::class, $all[0]);
    $this->assertInstanceOf(Job::class, $all[1]);
  }
}