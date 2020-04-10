<?php namespace rdbms;

/**
 * Represents a database constaint
 */
abstract class DBConstraint {

  public 
    $name= '';

  /**
   * Set name
   *
   * @param   string name
   */
  public function setName($name) {
    $this->name= $name;
  }

  /**
   * Get name
   *
   * @return  string
   */
  public function getName() {
    return $this->name;
  }
}