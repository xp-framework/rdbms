<?php namespace rdbms;

/**
 * Represents a fragment that can be rendered to string. Base interface
 * for SQLFragment and Projection interfaces.
 *
 * @see   xp://rdbms.SQLFragment
 * @see   xp://rdbms.criterion.Projection
 */
interface SQLRenderable {

  /**
   * Returns the fragment SQL
   *
   * @param   rdbms.DBConnection conn
   * @return  string
   * @throws  rdbms.SQLStateException
   */
  public function asSql(DBConnection $conn);
}
