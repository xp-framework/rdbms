<?php namespace rdbms\finder;

/**
 * Represents a finder method. Finder methods are methods inside
 * a rdbms.finder.Finder subclass that are decorated with the
 * "finder" annotation.
 *
 * There are two kinds of finder methods
 * <ol>
 *   <li>Those that return a single entity, finder(kind= ENTITIY)</li>
 *   <li>Those that return a collection fo entities, finder(kind= COLLECTION)</li>
 * </ol>
 *
 * @see      xp://rdbms.finder.Finder
 * @purpose  Method wrapper
 */
class FinderMethod {
  protected
    $finder= null, 
    $method= null;

  /**
   * Constructor
   *
   * @param   rdbms.finder.Finder finder
   * @param   lang.reflect.Method method
   */
  public function __construct($finder, $method) {
    $this->finder= $finder;
    $this->method= $method;

  }
  
  /**
   * Gets this method's kind
   *
   * @return  string kind one of ENTITY | COLLECTION
   */
  public function getKind() {
    return $this->method->getAnnotation('finder', 'kind');
  }
 
  /**
   * Returns this method's name
   *
   * @return  string method name
   */
  public function getName() {
    return $this->method->getName();
  }

  /**
   * Get the finder instance associated with this finder method
   *
   * @return  rdbms.finder.Finder
   */
  public function getFinder() {
    return $this->finder;
  }
    
  /**
   * Creates a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return sprintf(
      '%s(%s %s::%s())',
      nameof($this),
      $this->getKind(),
      nameof($this->finder),
      $this->method->getName()
    );
  }

  /**
   * Invokes this method
   *
   * @param   var[] args default array()
   * @return  var
   */
  public function invoke($args= []) {
    try {
      return $this->method->invoke($this->finder, $args);
    } catch (\lang\Throwable $e) {
      throw new FinderException($this->method->getName().' invocation failed', $e);
    }
  }
}