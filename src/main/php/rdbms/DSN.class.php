<?php namespace rdbms;

use lang\Value;
use peer\URL;
use util\Objects;

define('DB_STORE_RESULT',     0x0001);
define('DB_UNBUFFERED',       0x0002);
define('DB_AUTOCONNECT',      0x0004);
define('DB_PERSISTENT',       0x0008);
define('DB_NEWLINK',          0x0010);

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
  public 
    $url      = null,
    $flags    = 0,
    $prop     = [];
    
  /**
   * Constructor
   *
   * @param  string $str
   */
  public function __construct($str) {
    $this->url= new URL($str);
    if ($config= $this->url->getParams()) {
      foreach ($config as $key => $value) {
        if (defined('DB_'.strtoupper($key))) {
          if ($value) $this->flags= $this->flags | constant('DB_'.strtoupper($key));
        } else {
          $this->prop[$key]= $value;
        }
      }
    }
  }
  
  /**
   * Retrieve flags
   *
   * @return  int flags
   */
  public function getFlags() {
    return $this->flags;
  }
  
  /**
   * Get a property by its name
   *
   * @param   string name
   * @param   string defaullt default NULL
   * @return  string property or the default value if the property does not exist
   */
  public function getProperty($name, $default= null) {
    return isset($this->prop[$name]) ? $this->prop[$name] : $default;
  }

  /**
   * Retrieve value of a given parameter
   *
   * @param   string key
   * @param   string defaullt default NULL
   * @return  string value
   */
  #[@deprecated('Duplicates getProperty()')]
  public function getValue($key, $default= null) {
    if (!isset($this->parts['query'])) return $default;
    
    parse_str($this->parts['query'], $config);
    return isset($config[$key]) ? $config[$key] : $default;
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
        [$this->asString(true, false), $this->flags, $this->prop],
        [$value->asString(true, false), $value->flags, $value->prop]
      )
      : 1
    ;
  }
}
