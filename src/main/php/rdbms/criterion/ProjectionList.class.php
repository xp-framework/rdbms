<?php namespace rdbms\criterion;

/**
 * Stores a list of projections
 * 
 * Do not use this class, use the factory rdbms.criterion.Projections instead
 * <code>
 *   // select only the fields name and surname from the table Person
 *   // sql: select name, surname from person;
 *   Person::getPeer()->doSelect(create(new Criteria())->setProjection(
 *     Projections::projectionList()
 *     ->add(Person::column('name'))
 *     ->add(Person::column('surname'))
 *   ));
 *   
 *   // is short for
 *   Person::getPeer()->doSelect(create(new Criteria())->setProjection(
 *     Projections::projectionList()
 *     ->add(Projections::property(Person::column('name')))
 *     ->add(Projections::property(Person::column('surname')))
 *   ));
 *   
 *   // you can define aliases an use functions
 *   // sql: select concat(name, surname) as name from person;
 *   Person::getPeer()->doSelect(create(new Criteria())->setProjection(
 *     SQLFunctions::concat(
 *       Person::column('name'), ' ', Person::column('surname')
 *     ),
 *     'name'
 *   ));
 * </code>
 *
 * @test  xp://net.xp_framework.unittest.rdbms.ProjectionListTest
 * @see   xp://rdbms.criterion.Projections
 */
class ProjectionList implements Projection {
  protected $projections= [];

  /**
   * Add a new row to the result set.
   * Param can also be a rdbms.Column, a property
   * projection will be assumed then.
   *
   * @param    rdbms.SQLRenderable projection
   * @param    string alias optional
   * @return   rdbms.criterion.ProjectionList
   */
  public function add(\rdbms\SQLRenderable $projection, $alias= '') {
    $this->projections[]= [
      'alias'      => (empty($alias) && ($projection instanceof CountProjection) ? 'count' : $alias),
      'projection' => ($projection instanceof Projection) ? $projection : $projection= Projections::property($projection)
    ];
    return $this;
  }

  /**
   * Returns the fragment SQL string
   *
   * @param   rdbms.DBConnection conn
   * @return  string
   * @throws  rdbms.SQLStateException
   */
  public function asSql(\rdbms\DBConnection $conn) {
    $s= '';
    foreach ($this->projections as $e) {
      $s.= (0 != strlen($e['alias']))
        ? $conn->prepare(', %c as %l', $e['projection']->asSql($conn), $e['alias'])
        : $conn->prepare(', %c', $e['projection']->asSql($conn))
      ;
    }
    return substr($s, 1);
  }
}