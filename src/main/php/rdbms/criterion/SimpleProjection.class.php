<?php namespace rdbms\criterion;

use rdbms\{DBConnection, SQLRenderable};

/**
 * Belongs to the Criterion projection API simple base class.
 * Do not use, use factory rdbms.criterion.Projections instead
 *
 * @see   xp://rdbms.criterion.Projections
 * @see   xp://rdbms.criterion.CountProjection
 * @see   xp://rdbms.criterion.ProjectionList
 */
class SimpleProjection implements Projection {
  protected $field, $command;

  /**
   * constructor
   *
   * @param  rdbms.SQLRenderable field
   * @param  string command from Projection::constlist
   * @param  string alias optional
   */
  public function __construct(SQLRenderable $field, $command) {
    $this->field= $field;
    $this->command= $command;
  }

  /**
   * Returns the fragment SQL
   *
   * @param   rdbms.DBConnection conn
   * @param   rdbms.Peer peer
   * @return  string
   * @throws  rdbms.SQLStateException
   */
  public function asSql(DBConnection $conn) {
    return $conn->prepare($this->command, $this->field);
  }
}