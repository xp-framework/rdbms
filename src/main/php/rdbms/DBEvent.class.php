<?php namespace rdbms;

use util\Objects;

/**
 * Generic DB event.
 *
 * @purpose  Wrap database events
 */
class DBEvent {
  const
    CONNECT   = 'connect',
    CONNECTED = 'connected',
    QUERY     = 'query',
    QUERYEND  = 'queryend',
    IDENTITY  = 'identity';

  public
    $name=  '',
    $arg=   null;

  /**
   * Constructor.
   *
   */
  public function __construct($name, $arg= null) {
    $this->name=  $name;
    $this->arg=   $arg;
  }

  /**
   * Set Name
   *
   * @param   string name
   */
  public function setName($name) {
    $this->name= $name;
  }

  /**
   * Get Name
   *
   * @return  string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Set Arg
   *
   * @param   lang.Object arg
   */
  public function setArgument($arg) {
    $this->arg= $arg;
  }

  /**
   * Get Arg
   *
   * @return  lang.Object
   */
  public function getArgument() {
    return $this->arg;
  }
  
  /**
   * Return the string representation for this event.
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'('.$this->name.') {'.Objects::stringOf($this->arg).'}';
  }
}
