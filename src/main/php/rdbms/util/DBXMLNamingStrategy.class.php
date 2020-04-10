<?php namespace rdbms\util;

/**
 * Generate Names for database generated classes
 *
 * @deprecated
 */
abstract class DBXMLNamingStrategy {
  
  /**
   * assemble th name of a foreign key constraint
   *
   * @param   rdbms.DBTable t referenced table
   * @param   rdbms.DBConstraint c
   * @return  string
   */
  abstract function foreignKeyConstraintName($t, $c);

  /**
   * assemble the name of a referencing foreign Key constraint
   * (current entity at the tip)
   *
   * @param   rdbms.DBTable t referencing table
   * @param   rdbms.DBConstraint c
   * @return  string
   */
  abstract function referencingForeignKeyConstraintName($t, $c);
}