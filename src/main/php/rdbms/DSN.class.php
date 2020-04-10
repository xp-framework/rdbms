<?php namespace rdbms;

use lang\Value;
use peer\URL;
use util\Objects;

/**
 * DSN
 *
 * DSN syntax:
 * ```
 * driver://[username[:password]]@host[:port][/database][?flag=value[&flag2=value2]]
 * ```
 *
 * @test  xp://net.xp_framework.unittest.rdbms.DSNTest
 */
class DSN implements Value {
  public $url;
    
  /**
   * Constructor
   *
   * @param  string $str
   */
  public function __construct($str) {
    $this->url= new URL($str);
  }

  /**
   * Get a property by its name
   *
   * @param   string name
   * @param   string defaullt default NULL
   * @return  string property or the default value if the property does not exist
   */
  public function getProperty($name, $default= null) {
    return $this->url->getParam($name, $default);
  }

  /**
   * Retrieve driver
   *
   * @param   var default default NULL  
   * @return  string driver or default if none is set
   */
  public function getDriver($default= null) {
    return $this->url->getScheme() ? $this->url->getScheme() : $default;
  }
  
  /**
   * Retrieve host
   *
   * @param   var default default NULL  
   * @return  string host or default if none is set
   */
  public function getHost($default= null) {
    return $this->url->getHost() ? $this->url->getHost() : $default;
  }

  /**
   * Retrieve port
   *
   * @param   var default default NULL  
   * @return  string host or default if none is set
   */
  public function getPort($default= null) {
    return $this->url->getPort() ? $this->url->getPort() : $default;
  }

  /**
   * Retrieve database
   *
   * @param   var default default NULL  
   * @return  string databse or default if none is set
   */
  public function getDatabase($default= null) {
    $path= $this->url->getPath();
    return ('/' === $path || null === $path) ? $default : substr($path, 1);
  }

  /**
   * Retrieve user
   *
   * @param   var default default NULL  
   * @return  string user or default if none is set
   */
  public function getUser($default= null) {
    return $this->url->getUser() ? $this->url->getUser() : $default;
  }

  /**
   * Retrieve password
   *
   * @param   var default default NULL  
   * @return  string password or default if none is set
   */
  public function getPassword($default= null) {
    return $this->url->getPassword() ? $this->url->getPassword() : $default;
  }

  /**
   * Returns a string representation of this object
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'@('.$this->asString().')';
  }

  /**
   * Returns a string representation of this object, by default anonymizing
   * the password.
   *
   * @param  bool $password
   * @param  bool $query
   * @return string
   */
  public function asString($password= false, $query= true) {
    $pass= ($password
      ? ':'.$this->url->getPassword()
      : ($this->url->getPassword() ? ':********' : '')
    );
    return sprintf('%s://%s%s%s/%s%s',
      $this->url->getScheme(),
      ($this->url->getUser()
        ? $this->url->getUser().$pass.'@'
        : ''
      ),
      $this->url->getHost(),
      ($this->url->getPort()
        ? ':'.$this->url->getPort()
        : ''
      ),
      $this->getDatabase() ? $this->getDatabase() : '',
      $query && $this->url->getQuery() ? '?'.$this->url->getQuery() : ''
    );
  }

  /**
   * Returns a new DSN equal to this except for a NULLed password
   *
   * @return  self
   */
  public function withoutPassword() {
    $clone= clone $this;
    $clone->url->setPassword(null);
    return $clone;
  }

  /**
   * Clone callback method; clone embedded URL, too, so we're safe to change it
   * without changing the original
   */
  public function __clone() {
    $this->url= clone $this->url;
  }

  /**
   * Returns a hashcode for this object
   *
   * @return  string
   */
  public function hashCode() {
    return 'D'.md5($this->asString());
  }

  /**
   * Compares this DSN to another given value
   *
   * @param  var $value
   * @return int
   */    
  public function compareTo($value) {
    return $value instanceof self
      ? Objects::compare(
        [$this->asString(true, false), $this->url->getParams()],
        [$value->asString(true, false), $value->url->getParams()]
      )
      : 1
    ;
  }
}