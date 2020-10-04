<?php namespace rdbms\unittest\dataset;
 
use rdbms\join\JoinExtractable;
use rdbms\{CachedResults, DataSet};

/**
 * Class wrapper for table person, database JOBS
 * (Auto-generated on Wed, 16 May 2007 14:44:35 +0200 by ruben)
 *
 * @purpose  Datasource accessor
 */
class Person extends DataSet implements JoinExtractable {
  public
    $person_id          = 0,
    $name               = '',
    $job_id             = 0,
    $department_id      = 0;

  protected
    $cache= [
      'Department' => [],
      'Job' => [],
      'DepartmentChief' => [],
    ];

  static function __static() { 
    with ($peer= self::getPeer()); {
      $peer->setTable('JOBS.Person');
      $peer->setConnection('jobs');
      $peer->setIdentity('person_id');
      $peer->setPrimary(['person_id']);
      $peer->setTypes([
        'person_id'     => ['%d', \rdbms\FieldType::NUMERIC, false],
        'name'          => ['%s', \rdbms\FieldType::VARCHAR, false],
        'job_id'        => ['%d', \rdbms\FieldType::NUMERIC, false],
        'department_id' => ['%d', \rdbms\FieldType::NUMERIC, false],
      ]);
      $peer->setRelations([
        'Department' => [
          'classname' => 'rdbms.unittest.dataset.Department',
          'key'       => [
            'department_id' => 'department_id',
          ],
        ],
        'Job' => [
          'classname' => 'rdbms.unittest.dataset.Job',
          'key'       => [
            'job_id' => 'job_id',
          ],
        ],
        'DepartmentChief' => [
          'classname' => 'rdbms.unittest.dataset.Department',
          'key'       => [
            'person_id' => 'chief_id',
          ],
        ],
      ]);
    }
  }  

  /**
   * Retrieve associated peer
   *
   * @return  rdbms.Peer
   */
  public static function getPeer() {
    return \rdbms\Peer::forName(self::class);
  }

  /**
   * column factory
   *
   * @param   string name
   * @return  rdbms.Column
   * @throws  lang.IllegalArgumentException
   */
  static public function column($name) {
    return self::getPeer()->column($name);
  }

  /**
   * Gets an instance of this object by index "PRIMARY"
   * 
   * @param   int person_id
   * @return  net.xp_framework.unittest.rdbms.dataset.Person entitiy object
   * @throws  rdbms.SQLException in case an error occurs
   */
  public static function getByPerson_id($person_id) {
    $r= self::getPeer()->doSelect(new \rdbms\Criteria(['person_id', $person_id, EQUAL]));
    return $r ? $r[0] : null;    }

  /**
   * Gets an instance of this object by index "job"
   * 
   * @param   int job_id
   * @return  net.xp_framework.unittest.rdbms.dataset.Person[] entity objects
   * @throws  rdbms.SQLException in case an error occurs
   */
  public static function getByJob_id($job_id) {
    return self::getPeer()->doSelect(new \rdbms\Criteria(['job_id', $job_id, EQUAL]));    }

  /**
   * Gets an instance of this object by index "department"
   * 
   * @param   int department_id
   * @return  net.xp_framework.unittest.rdbms.dataset.Person[] entity objects
   * @throws  rdbms.SQLException in case an error occurs
   */
  public static function getByDepartment_id($department_id) {
    return self::getPeer()->doSelect(new \rdbms\Criteria(['department_id', $department_id, EQUAL]));    }

  /**
   * Retrieves person_id
   *
   * @return  int
   */
  public function getPerson_id() {
    return $this->person_id;
  }
    
  /**
   * Sets person_id
   *
   * @param   int person_id
   * @return  int the previous value
   */
  public function setPerson_id($person_id) {
    return $this->_change('person_id', $person_id);
  }

  /**
   * Retrieves name
   *
   * @return  string
   */
  public function getName() {
    return $this->name;
  }
    
  /**
   * Sets name
   *
   * @param   string name
   * @return  string the previous value
   */
  public function setName($name) {
    return $this->_change('name', $name);
  }

  /**
   * Retrieves job_id
   *
   * @return  int
   */
  public function getJob_id() {
    return $this->job_id;
  }
    
  /**
   * Sets job_id
   *
   * @param   int job_id
   * @return  int the previous value
   */
  public function setJob_id($job_id) {
    return $this->_change('job_id', $job_id);
  }

  /**
   * Retrieves department_id
   *
   * @return  int
   */
  public function getDepartment_id() {
    return $this->department_id;
  }
    
  /**
   * Sets department_id
   *
   * @param   int department_id
   * @return  int the previous value
   */
  public function setDepartment_id($department_id) {
    return $this->_change('department_id', $department_id);
  }

  /**
   * Retrieves the Department entity
   * referenced by department_id=>department_id
   *
   * @return  net.xp_framework.unittest.rdbms.dataset.Department entity
   * @throws  rdbms.SQLException in case an error occurs
   */
  public function getDepartment() {
    $r= ($this->cached['Department']) ?
      array_values($this->cache['Department']) :
      \lang\XPClass::forName('rdbms.unittest.dataset.Department')
        ->getMethod('getPeer')
        ->invoke()
        ->doSelect(new \rdbms\Criteria(
        ['department_id', $this->getDepartment_id(), EQUAL]
    ));
    return $r ? $r[0] : null;
  }

  /**
   * Retrieves the Job entity
   * referenced by job_id=>job_id
   *
   * @return  net.xp_framework.unittest.rdbms.dataset.Job entity
   * @throws  rdbms.SQLException in case an error occurs
   */
  public function getJob() {
    $r= ($this->cached['Job']) ?
      array_values($this->cache['Job']) :
      \lang\XPClass::forName('rdbms.unittest.dataset.Job')
        ->getMethod('getPeer')
        ->invoke()
        ->doSelect(new \rdbms\Criteria(
        ['job_id', $this->getJob_id(), EQUAL]
    ));
    return $r ? $r[0] : null;
  }

  /**
   * Retrieves an array of all Department entities referencing
   * this entity by chief_id=>person_id
   *
   * @return  net.xp_framework.unittest.rdbms.dataset.Department[] entities
   * @throws  rdbms.SQLException in case an error occurs
   */
  public function getDepartmentChiefList() {
    if ($this->cached['DepartmentChief']) return array_values($this->cache['DepartmentChief']);
    return \lang\XPClass::forName('rdbms.unittest.dataset.Department')
      ->getMethod('getPeer')
      ->invoke()
      ->doSelect(new \rdbms\Criteria(
        ['chief_id', $this->getPerson_id(), EQUAL]
    ));
  }

  /**
   * Retrieves an iterator for all Department entities referencing
   * this entity by chief_id=>person_id
   *
   * @return  util.XPIterator
   * @throws  rdbms.SQLException in case an error occurs
   */
  public function getDepartmentChiefIterator() {
    if ($this->cached['DepartmentChief']) return new CachedResults($this->cache['DepartmentChief']);
    return \lang\XPClass::forName('rdbms.unittest.dataset.Department')
      ->getMethod('getPeer')
      ->invoke()
      ->iteratorFor(new \rdbms\Criteria(
        ['chief_id', $this->getPerson_id(), EQUAL]
    ));
  }
}