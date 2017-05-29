<?php namespace rdbms\finder;

/**
 * Abstract base class for finder delegates
 *
 */
abstract class FinderDelegate {
  protected $finder= null;

  /**
   * Creates a new instance with a given finder
   *
   * @param   rdbms.finder.Finder finder
   */
  public function __construct($finder) {
    $this->finder= $finder;
  }
  
  /**
   * Select implementation
   *
   * @param   rdbms.Criteria criteria
   * @return  var
   * @throws  rdbms.finder.FinderException
   */
  public abstract function select($criteria);
  
  /**
   * Fluent interface
   *
   * @param   string name
   * @param   var[] args
   * @return  var
   */
  public function __call($name, $args) {
    if (method_exists($this->finder, $name)) {
      return $this->select($this->finder->{$name}(...$args));
    } else {
      throw new FinderException('No such method '.$name.' in '.nameof($this->finder));
    }
  }
}
