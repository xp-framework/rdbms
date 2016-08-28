<?php namespace rdbms;
 
use util\log\Logger;
use util\Observable;
use util\TimeZone;
use lang\XPClass;

/**
 * Provide an interface from which all other database connection
 * classes extend.
 */
abstract class DBConnection extends Observable {
  public 
    $handle  = null,
    $dsn     = null,
    $tz      = null,
    $timeout = 0,
    $flags   = 0;
  
  protected
    $formatter= null;

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) {
    $this->dsn= $dsn;
    $this->flags= $dsn->getFlags();
    if (!$this->dsn->url->hasParam('autoconnect')) {
      $this->flags |= DB_AUTOCONNECT;
    }
    $this->setTimeout($dsn->getProperty('timeout', 0));   // 0 means no timeout
    
    // Keep this for BC reasons
    $observers= $dsn->getProperty('observer', []);
    if (null !== ($cat= $dsn->getProperty('log'))) { 
      $observers['util.log.LogObserver']= $cat; 
    }
    
    // Add observers
    foreach ($observers as $observer => $param) {
      $class= XPClass::forName($observer);

      // Check if class implements BoundLogObserver: in that case use factory method to acquire
      // instance. Otherwise, just use the constructor
      if (XPClass::forName('util.log.BoundLogObserver')->isAssignableFrom($class)) {
        $this->addObserver($class->getMethod('instanceFor')->invoke(null, [$param]));
      } else {
        $this->addObserver($class->newInstance($param));
      }
    }

    // Time zone handling
    if ($tz= $dsn->getProperty('timezone', false)) {
      $this->tz= new TimeZone($tz);
    }
  }

  /**
   * Retrieve DSN
   *
   * @return rdbms.DSN
   */
  public function getDSN() {
    return $this->dsn;
  }
  
  /**
   * Returns a hashcode for this connection
   *
   * Example:
   * <pre>
   *   sybase-ct link #50
   * </pre>
   *
   * @return  string
   */
  public function hashCode() {
    return get_resource_type($this->handle).' #'.(int)$this->handle;
  }
  
  /**
   * Creates a string representation of this connection
   *
   * @return  string
   */
  public function toString() {
    return sprintf(
      '%s(->%s%s%s)',
      nameof($this),
      $this->dsn->asString(),
      $this->tz ? ', tz='.$this->tz->toString() : '',
      $this->handle ? ', conn='.get_resource_type($this->handle).' #'.(int)$this->handle : ''
    );
  }

  /**
   * Set Timeout
   *
   * @param   int timeout
   */
  public function setTimeout($timeout) {
    $this->timeout= $timeout;
  }

  /**
   * Get Timeout
   *
   * @return  int
   */
  public function getTimeout() {
    return $this->timeout;
  }
  
  /**
   * Set a flag
   *
   * @param   int flag
   */
  public function setFlag($flag) { 
    $this->flags |= $flag;
  }
  
  /**
   * Connect
   *
   * @return  bool success
   */
  public function connect() { 
    if ($db= $this->dsn->getDatabase()) {
      return $this->selectdb($db);
    }
    
    return true;
  }
  
  /**
   * Checks whether changed flag is set
   *
   * @return  bool
   */
  public function hasChanged() {
    return true;
  }

  /**
   * Disconnect
   *
   * @return  bool success
   */
  abstract public function close();
  
  /**
   * Select database
   *
   * @param   string db name of database to select
   * @return  bool success
   */
  abstract public function selectdb($db);

  /**
   * Prepare an SQL statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return string
   */
  public function prepare($statement, ...$args) {
    return $this->formatter->format($statement, $args);
  }
  
  /**
   * Retrieve number of affected rows
   *
   * @return  int
   */
  protected function affectedRows() {}
  
  /**
   * Execute an insert statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return int number of affected rows
   * @throws rdbms.SQLStatementFailedException
   */
  public function insert($statement, ...$args) {
    return $this->query('insert '.$statement, ...$args)->affected();
  }
  
  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  abstract public function identity($field= null);

  /**
   * Execute an update statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return int number of affected rows
   * @throws rdbms.SQLStatementFailedException
   */
  public function update($statement, ...$args) {
    return $this->query('update '.$statement, ...$args)->affected();
  }
  
  /**
   * Execute an update statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return int number of affected rows
   * @throws rdbms.SQLStatementFailedException
   */
  public function delete($statement, ...$args) {
    return $this->query('delete '.$statement, ...$args)->affected();
  }
  
  /**
   * Execute a select statement and return all rows as an array
   *
   * @param  string $statement
   * @param  var... $args
   * @return var[] rowsets
   * @throws rdbms.SQLStatementFailedException
   */
  public function select($statement, ...$args) {
    $q= $this->query('select '.$statement, ...$args);

    $rows= [];
    while ($row= $q->next()) $rows[]= $row;
    return $rows;
  }

  /**
   * Execute any statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return rdbms.ResultSet
   * @throws rdbms.SQLException
   */
  public function query($statement, ...$args) {
    $sql= $this->formatter->format($statement, $args);

    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::QUERY, $sql));
    $result= $this->query0($sql);
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::QUERYEND, $result));

    return $result;
  }

  /**
   * Execute any statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return rdbms.ResultSet
   * @throws rdbms.SQLException
   */
  public function open($statement, ...$args) { 
    $sql= $this->formatter->format($statement, $args);

    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::QUERY, $sql));
    $result= $this->query0($sql, false);
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::QUERYEND, $result));
    return $result;
  }
  
  /**
   * Execute any statement
   *
   * @param   string sql
   * @param   bool buffered default TRUE
   * @return  rdbms.ResultSet
   * @throws  rdbms.SQLException
   */
  protected function query0($sql, $buffered= true) { }
  
  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  abstract public function begin($transaction);
  
  /**
   * Retrieve transaction state
   *
   * @param   string name
   * @return  var state
   */
  public function transtate($name) { }
  
  /**
   * Rollback a transaction
   *
   * @param   string name
   * @return  bool success
   */
  abstract public function rollback($name);
  
  /**
   * Commit a transaction
   *
   * @param   string name
   * @return  bool success
   */
  abstract public function commit($name);
  
  /**
   * Retrieve SQL formatter
   *
   * @return  rdbms.StatementFormatter
   */
  public function getFormatter() {
    return $this->formatter;
  }
}
