<?php namespace rdbms;

use util\Objects;

/**
 * Represents an SQL standard procedure
 */
class SQLFunction implements SQLFragment {
  public
    $func = '',
    $type = '%s',
    $args = [];

  /**
   * Constructor
   *
   * @param   string function
   * @param   string type one of the %-tokens
   * @param   var[] arguments
   */
  public function __construct($function, $type, $arguments= []) {
    $this->func= $function;
    $this->type= $type;
    $this->args= $arguments;
  }

  /**
   * Returns the fragment SQL
   *
   * @param   rdbms.DBConnection conn
   * @return  string
   * @throws  rdbms.SQLStateException
   */
  public function asSql(DBConnection $conn) {
    return $conn->prepare($conn->getFormatter()->dialect->formatFunction($this), ...$this->args);
  }

  /**
   * Set func
   *
   * @param   string func
   */
  public function setFunc($func) {
    $this->func= $func;
  }

  /**
   * Get func
   *
   * @return  string
   */
  public function getFunc() {
    return $this->func;
  }

  /**
   * Set args
   *
   * @param   var[] args
   */
  public function setArgs($args) {
    $this->args= $args;
  }

  /**
   * Get args
   *
   * @return  var[]
   */
  public function getArgs() {
    return $this->args;
  }

  /**
   * Return type this function evaluates to
   *
   * @return  string
   */
  public function getType() {
    return $this->type; 
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'<'.$this->type.' '.$this->func.' ('.Objects::stringOf($this->args).')>';
  }
}
