<?php namespace rdbms\unittest;

use rdbms\criterion\{Projections, Restrictions};
use rdbms\mysql\MySQLConnection;
use rdbms\pgsql\PostgreSQLConnection;
use rdbms\sqlite3\SQLite3Connection;
use rdbms\sybase\SybaseConnection;
use rdbms\unittest\dataset\Job;
use rdbms\unittest\mock\{MockResultSet, RegisterMockConnection};
use rdbms\{Criteria, DriverManager, Record};
use unittest\Test;
use util\Date;

/**
 * TestCase
 *
 * @see  xp://rdbms.criterion.Projections
 */
#[Action(eval: 'new RegisterMockConnection()')]
class ProjectionTest extends \unittest\TestCase {
  public
    $syconn = null,
    $myconn = null,
    $pgconn = null,
    $sqconn = null,
    $peer   = null;
    
  /**
   * Sets up a Database Object for the test
   *
   */
  public function setUp() {
    $this->syconn= new SybaseConnection(new \rdbms\DSN('sybase://localhost:1999/'));
    $this->myconn= new MySQLConnection(new \rdbms\DSN('mysql://localhost/'));
    $this->pgconn= new PostgreSQLConnection(new \rdbms\DSN('pgsql://localhost/'));
    $this->sqconn= new SQLite3Connection(new \rdbms\DSN('sqlite://tmpdir/tmpdb'));
    $this->peer= Job::getPeer();
  }
  
  /**
   * Helper method that will call toSQL() on the passed criteria and
   * compare the resulting string to the expected string.
   *
   * @param   string mysql
   * @param   string sysql
   * @param   string pgsql
   * @param   string sqlite
   * @param   rdbms.Criteria criteria
   * @throws  unittest.AssertionFailedError
   */
  protected function assertSql($mysql, $sysql, $pgsql, $sqlite, $criteria) {
    $this->assertEquals('mysql: '.$mysql,  'mysql: '.trim($criteria->toSQL($this->myconn, $this->peer), ' '));
    $this->assertEquals('sybase: '.$sysql, 'sybase: '.trim($criteria->toSQL($this->syconn, $this->peer), ' '));
    $this->assertEquals('pgsql: '.$pgsql, 'pgsql: '.trim($criteria->toSQL($this->pgconn, $this->peer), ' '));
    $this->assertEquals('sqlite: '.$sqlite, 'sqlite: '.trim($criteria->toSQL($this->sqconn, $this->peer), ' '));
  }
  
  /**
   * Helper method that will call projection() on the passed criteria and
   * compare the resulting string to the expected string.
   *
   * @param   string mysql
   * @param   string sysql
   * @param   string pgsql
   * @param   string sqlite
   * @param   rdbms.Criteria criteria
   * @throws  unittest.AssertionFailedError
   */
  protected function assertProjection($mysql, $sysql, $pgsql, $sqlite, $criteria) {
    $this->assertEquals('mysql: '.$mysql,  'mysql: '.trim($criteria->projections($this->myconn, $this->peer), ' '));
    $this->assertEquals('sybase: '.$sysql, 'sybase: '.trim($criteria->projections($this->syconn, $this->peer), ' '));
    $this->assertEquals('pgsql: '.$pgsql, 'pgsql: '.trim($criteria->projections($this->pgconn, $this->peer), ' '));
    $this->assertEquals('sqlite: '.$sqlite, 'sqlite: '.trim($criteria->projections($this->sqconn, $this->peer), ' '));
  }
  
  #[Test]
  function countTest() {
    $this->assertProjection(
      'count(*) as `count`',
      'count(*) as \'count\'',
      'count(*) as "count"',
      'count(*) as \'count\'',
      (new Criteria())->setProjection(Projections::count())
    );
  }

  #[Test]
  function countColumnTest() {
    $this->assertProjection(
      'count(job_id) as `count_job_id`',
      'count(job_id) as \'count_job_id\'',
      'count(job_id) as "count_job_id"',
      'count(job_id) as \'count_job_id\'',
      (new Criteria())->setProjection(Projections::count(Job::column('job_id')), 'count_job_id')
    );
  }

  #[Test]
  function countColumnAliasTest() {
    $this->assertProjection(
      'count(job_id) as `counting all`',
      'count(job_id) as \'counting all\'',
      'count(job_id) as "counting all"',
      'count(job_id) as \'counting all\'',
      (new Criteria())->setProjection(Projections::count(Job::column('job_id')), "counting all")
    );
  }

  #[Test]
  function countAliasTest() {
    $this->assertProjection(
      'count(*) as `counting all`',
      'count(*) as \'counting all\'',
      'count(*) as "counting all"',
      'count(*) as \'counting all\'',
      (new Criteria())->setProjection(Projections::count('*'), "counting all")
    );
  }

  #[Test]
  function avgTest() {
    $this->assertProjection(
      'avg(job_id)',
      'avg(job_id)',
      'avg(job_id)',
      'avg(job_id)',
      (new Criteria())->setProjection(Projections::average(Job::column("job_id")))
    );
  }

  #[Test]
  function sumTest() {
    $this->assertProjection(
      'sum(job_id)',
      'sum(job_id)',
      'sum(job_id)',
      'sum(job_id)',
      (new Criteria())->setProjection(Projections::sum(Job::column("job_id")))
    );
  }

  #[Test]
  function minTest() {
    $this->assertProjection(
      'min(job_id)',
      'min(job_id)',
      'min(job_id)',
      'min(job_id)',
      (new Criteria())->setProjection(Projections::min(Job::column("job_id")))
    );
  }

  #[Test]
  function maxTest() {
    $this->assertProjection(
      'max(job_id)',
      'max(job_id)',
      'max(job_id)',
      'max(job_id)',
      (new Criteria())->setProjection(Projections::max(Job::column("job_id")))
    );
  }

  #[Test]
  function propertyTest() {
    $this->assertProjection(
      'job_id',
      'job_id',
      'job_id',
      'job_id',
      (new Criteria())->setProjection(Projections::property(Job::column("job_id")))
    );
  }

  #[Test]
  function propertyListTest() {
    $this->assertProjection(
      'job_id, title',
      'job_id, title',
      'job_id, title',
      'job_id, title',
      (new Criteria())->setProjection(Projections::projectionList()
        ->add(Projections::property(Job::column('job_id')))
        ->add(Projections::property(Job::column('title')))
    ));
    $this->assertInstanceOf(
      'rdbms.criterion.ProjectionList',
      Projections::projectionList()->add(Projections::property(Job::column('job_id')))
    );
  }

  #[Test]
  function propertyListAliasTest() {
    $this->assertProjection(
      'job_id as `id`, title',
      'job_id as \'id\', title',
      'job_id as "id", title',
      'job_id as \'id\', title',
      (new Criteria())->setProjection(Projections::projectionList()
        ->add(Projections::property(Job::column('job_id')), 'id')
        ->add(Job::column('title'))
    ));
  }

  #[Test]
  function setProjectionTest() {
    $crit= new Criteria();
    $this->assertFalse($crit->isProjection());
    $crit->setProjection(Projections::property(Job::column('job_id')));
    $this->assertTrue($crit->isProjection());
    $crit->setProjection(null);
    $this->assertFalse($crit->isProjection());
    $crit->setProjection(Job::column('job_id'));
    $this->assertTrue($crit->isProjection());
    $crit->setProjection();
    $this->assertFalse($crit->isProjection());
  }

  #[Test]
  function withProjectionTest() {
    $crit= new Criteria();
    $this->assertInstanceOf(
      'rdbms.Criteria',
      $crit->withProjection(Projections::property(Job::column('job_id')))
    );
    $this->assertFalse($crit->isProjection());
    $this->assertTrue($crit->withProjection(Projections::property(Job::column('job_id')))->isProjection());
  }

  #[Test]
  function regressionIteratorDatasetType() {
    $conn= DriverManager::getConnection('mock://mock/JOBS?autoconnect=1');
    $conn->setResultSet(new MockResultSet([['count' => 5]]));
    $crit= (new Criteria())->withProjection(Projections::count('*'));
    $this->peer->setConnection($conn);
    $this->assertInstanceOf(Record::class, $this->peer->iteratorFor($crit)->next());
  }
}