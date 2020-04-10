<?php namespace rdbms;

/** 
 * Class representing an index
 *
 */
class DBIndex {
  public
    $name=     '',
    $keys=     [],
    $unique=   false,
    $primary=  false;

  /**
   * Constructor
   *
   * @param   string name
   * @param   string[] keys an array of keys this index is composed of
   */
  public function __construct($name, $keys) {
    $this->name= $name;
    $this->keys= $keys;
    
  }

  /**
   * Return whether this is the primary key
   *
   * @return  bool TRUE when this key is the primary key
   */
  public function isPrimaryKey() {
    return $this->primary;
  }

  /**
   * Return whether this index is unique
   *
   * @return  bool TRUE when this is a unique index
   */
  public function isUnique() {
    return $this->unique;
  }

  /**
   * Return this index' name
   *
   * @return  string name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns all keys
   *
   * @return  string[] keys
   */
  public function getKeys() {
    return $this->keys;
  }
}