<?php namespace rdbms\unittest;
 
use rdbms\{Criteria, DSN};
use rdbms\join\{JoinPart, JoinRelation, JoinTable};
use rdbms\mysql\MySQLConnection;
use rdbms\unittest\dataset\{Department, Job, Person};
use unittest\TestCase;

/**
 * Test JoinPart class
 *
 * Note: We're relying on the connection to be a mysql connection -
 * otherwise, quoting and date representation may change and make
 * this testcase fail.
 *
 * @see     xp://rdbms.Criteria
 */
class JoinPartTest extends TestCase {
  public $conn= null;
  public $peer= null;
    
  /**
   * Setup test
   */
  public function setUp() {
    $this->conn= new MySQLConnection(new DSN('mysql://localhost:3306/'));
  }

  #[@test]
  public function getAttributesTest() {
    $joinpart= new JoinPart('job', Job::getPeer());
    $this->assertEquals(
      $joinpart->getAttributes(),
      [
        'job.job_id as job_job_id',
        'job.title as job_title',
        'job.valid_from as job_valid_from',
        'job.expire_at as job_expire_at' ,
      ]
    );
  }

  #[@test]
  public function getTableTest() {
    $joinpart= new JoinPart('job', Job::getPeer());
    $this->assertInstanceOf(JoinTable::class, $joinpart->getTable());
    $this->assertEquals($joinpart->getTable()->toSqlString(), 'JOBS.job as job');
  }

  #[@test]
  public function getJoinRelationsTest() {
    $jobpart=    new JoinPart('j', Job::getPeer());
    $personpart= new JoinPart('p', Person::getPeer());

    $jobpart->addRelative($personpart, 'PersonJob');

    $this->assertInstanceOf('var[]', $jobpart->getJoinRelations());
    $j_p= current($jobpart->getJoinRelations());
    $this->assertInstanceOf(JoinRelation::class, $j_p);
    $this->assertInstanceOf(JoinTable::class, $j_p->getSource());
    $this->assertInstanceOf(JoinTable::class, $j_p->getTarget());
    $this->assertEquals(
      $j_p->getConditions(),
      ['j.job_id = p.job_id']
    );
  }

  #[@test]
  public function getComplexJoinRelationsTest() {
    $toJob=        new JoinPart('j', Job::getPeer());
    $toPerson=     new JoinPart('p', Person::getPeer());
    $toDepartment= new JoinPart('d', Department::getPeer());
    $toChief=      new JoinPart('c', Person::getPeer());

    $toJob->addRelative($toPerson, 'PersonJob');
    $toPerson->addRelative($toDepartment, 'Department');
    $toDepartment->addRelative($toChief, 'Chief');

    $this->assertEquals(
      $this->conn->getFormatter()->dialect->makeJoinBy($toJob->getJoinRelations()),
      'JOBS.job as j LEFT OUTER JOIN JOBS.Person as p on (j.job_id = p.job_id) LEFT JOIN JOBS.Department as d on (p.department_id = d.department_id) LEFT JOIN JOBS.Person as c on (d.chief_id = c.person_id) where '
    );
  }

  #[@test]
  public function extractTest() {
    $toJob=        new JoinPart('j', Job::getPeer());
    $toPerson=     new JoinPart('p', Person::getPeer());
    $toDepartment= new JoinPart('d', Department::getPeer());
    $toChief=      new JoinPart('c', Person::getPeer());

    $toJob->addRelative($toPerson, 'JobPerson');
    $toPerson->addRelative($toDepartment, 'Department');
    $toDepartment->addRelative($toChief, 'DepartmentChief');

    $job= Job::getPeer()->objectFor(
      [
        'job_id'     => '21',
        'title'      => 'clean the toilette',
        'valid_from' => new \util\Date(),
        'expire_at'  => '',
      ]
    );
    $toPerson->extract(
      $job,
      [
        'p_person_id'     => '11',
        'p_name'          => 'Schultz',
        'p_job_id'        => '21',
        'p_department_id' => '31',
        'd_department_id' => '31',
        'd_name'          => 'iDev',
        'd_chief_id'      => '12',
        'c_person_id'     => '12',
        'c_name'          => 'Friebe',
        'c_job_id'        => '22',
        'c_department_id' => '31',
      ],
      'JobPerson'
    );
    
    $this->assertInstanceOf(Person::class, $job->getCachedObj('JobPerson', '#11'));
    $this->assertInstanceOf(Department::class, $job->getCachedObj('JobPerson', '#11')->getCachedObj('Department', '#31'));
    $this->assertInstanceOf(Person::class, $job->getCachedObj('JobPerson', '#11')->getCachedObj('Department', '#31')->getCachedObj('DepartmentChief', '#12'));
  }
}