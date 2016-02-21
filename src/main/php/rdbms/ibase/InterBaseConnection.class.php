<?php namespace rdbms\ibase;

use rdbms\DBConnection;
use rdbms\Transaction;
use rdbms\StatementFormatter;
use rdbms\QuerySucceeded;

/**
 * Connection to InterBase/FireBird databases using client libraries
 *
 * @see      http://www.firebirdsql.org/
 * @see      http://www.borland.com/interbase/
 * @see      http://www.firebirdsql.org/doc/contrib/fb_2_1_errorcodes.pdf
 * @ext      interbase
 * @test     xp://net.xp_framework.unittest.rdbms.TokenizerTest
 * @test     xp://net.xp_framework.unittest.rdbms.DBTest
 * @purpose  Database connection
 */
class InterBaseConnection extends DBConnection {

  static function __static() {
    if (extension_loaded('interbase')) {
      \rdbms\DriverManager::register('ibase+std', new \lang\XPClass(__CLASS__));
    }
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) {
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new InterBaseDialect());
  }

  /**
   * Connect
   *
   * @param   bool reconnect default FALSE
   * @return  bool success
   * @throws  rdbms.SQLConnectException
   */
  public function connect($reconnect= false) {
    if (is_resource($this->handle)) return true;  // Already connected
    if (!$reconnect && (false === $this->handle)) return false;    // Previously failed connecting

    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECT, $reconnect));
    $db= $this->dsn->getHost().':'.$this->dsn->getDatabase();
    if ($this->flags & DB_PERSISTENT) {
      $this->handle= ibase_pconnect(
        $db, 
        $this->dsn->getUser(), 
        $this->dsn->getPassword(),
        'UTF_8'
      );
    } else {
      $this->handle= ibase_connect(
        $db, 
        $this->dsn->getUser(), 
        $this->dsn->getPassword(),
        'UTF_8'
      );
    }

    if (!is_resource($this->handle)) {
      throw new \rdbms\SQLConnectException(trim(ibase_errmsg()), $this->dsn);
    }
    
    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECTED, $reconnect));
    return true;
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() { 
    if ($this->handle && $r= ibase_close($this->handle)) {
      $this->handle= null;
      return $r;
    }
    return false;
  }
  
  /**
   * Select database
   *
   * @param   string db name of database to select
   * @return  bool success
   * @throws  rdbms.SQLStatementFailedException
   */
  public function selectdb($db) {
    throw new \rdbms\SQLStatementFailedException(
      'Cannot select database, not implemented in Interbase'
    );
  }
  
  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  public function identity($field= null) {
    $i= $this->query('select @@identity as i')->next('i');
    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::IDENTITY, $i));
    return $i;
  }

  /**
   * Retrieve number of affected rows for last query
   *
   * @return  int
   */
  protected function affectedRows() {
    return ibase_affected_rows($this->handle);
  }
  
  /**
   * Execute any statement
   *
   * @param   string sql
   * @param   bool buffered default TRUE
   * @return  rdbms.ResultSet
   * @throws  rdbms.SQLException
   */
  protected function query0($sql, $buffered= true) {
    if (!is_resource($this->handle)) {
      if (!($this->flags & DB_AUTOCONNECT)) throw new \rdbms\SQLStateException('Not connected');
      $c= $this->connect();
      
      // Check for subsequent connection errors
      if (false === $c) throw new \rdbms\SQLStateException('Previously failed to connect');
    }
    
    $result= ibase_query($sql, $this->handle);
    if (false === $result) {
      $message= 'Statement failed: '.trim(ibase_errmsg()).' @ '.$this->dsn->getHost();
      $code= ibase_errcode();
      switch ($code) {
        case -924:    // Connection lost
          throw new \rdbms\SQLConnectionClosedException($message, $sql);

        case -913:    // Deadlock
          throw new \rdbms\SQLDeadlockException($message, $sql, $code);

        default:      // Other error
          throw new \rdbms\SQLStatementFailedException($message, $sql, $code);
      }
    } else if (true === $result) {
      return new QuerySucceeded(ibase_affected_rows($this->handle));
    } else {
      return new InterBaseResultSet($result, $this->tz);
    }
  }
  
  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  public function begin($transaction) {
    $this->query('begin transaction xp_%c', $transaction->name);
    $transaction->db= $this;
    return $transaction;
  }
  
  /**
   * Retrieve transaction state
   *
   * @param   string name
   * @return  var state
   */
  public function transtate($name) { 
    return $this->query('select @@transtate as transtate')->next('transtate');
  }
  
  /**
   * Rollback a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function rollback($name) { 
    return $this->query('rollback transaction xp_%c', $name);
  }
  
  /**
   * Commit a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function commit($name) { 
    return $this->query('commit transaction xp_%c', $name);
  }
}
