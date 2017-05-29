<?php namespace rdbms;

/**
 * Driver implementations provider for driver manager
 *
 * @see   xp://rdbms.DefaultDrivers
 */
abstract class DriverImplementationsProvider {
  protected $parent= null;

  /**
   * Constructor
   *
   * @param   rdbms.DriverImplementationsProvider parent
   */
  public function __construct(self $parent= null) {
    $this->parent= $parent;
  }
  
  /**
   * Returns an array of class names implementing a given driver
   *
   * @param   string driver
   * @return  string[] implementations
   */
  public function implementationsFor($driver) {
    return null === $this->parent ? [] : $this->parent->implementationsFor($driver);
  }

  /** @return string[] */
  public function drivers() { return []; }

  /**
   * Creates a string representation of this implementation provider
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).(null === $this->parent ? '' : ', '.$this->parent->toString());
  }
}
