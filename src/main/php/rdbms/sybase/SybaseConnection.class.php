<?php namespace rdbms\sybase;

use rdbms\{DBConnection, QuerySucceeded, StatementFormatter, Transaction};

/**
 * Connection to Sybase databases using client libraries (sybase_ct)
 *
 * @see    http://sybase.com/
 * @ext    sybase_ct
 * @test   xp://net.xp_framework.unittest.rdbms.TokenizerTest
 * @test   xp://net.xp_framework.unittest.rdbms.DBTest
 */
class SybaseConnection extends DBConnection {

  static function __static() {
    if (extension_loaded('sybase_ct')) {
      ini_set('sybct.deadlock_retry_count', 0);
      \rdbms\DriverManager::register('sybase+ct', new \lang\XPClass(__CLASS__));
    }
  }
  
  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) {
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new SybaseDialect());
  }

  /**
   * Set Timeout
   *
   * @param   int timeout
   */
  public function setTimeout($timeout) {
    ini_set('sybct.login_timeout', $timeout);
    parent::setTimeout($timeout);
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

    $this->handle= sybase_connect(
      $this->dsn->getHost(),
      $this->dsn->getUser(),
      $this->dsn->getPassword(),
      'utf8',
      'XP',
      true
    );
    if (!is_resource($this->handle)) {
      $e= new \rdbms\SQLConnectException(trim(sybase_get_last_message()), $this->dsn);
      \xp::gc(__FILE__);
      throw $e;
    }
    \xp::gc(__FILE__);

    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECTED, $reconnect));
    return parent::connect();
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() { 
    $r= $this->handle && sybase_close($this->handle);
    $this->handle= null;
    return (bool)$r;
  }
  
  /**
   * Select database
   *
   * @param   string db name of database to select
   * @return  bool success
   * @throws  rdbms.SQLStatementFailedException
   */
  public function selectdb($db) {
    if (!sybase_select_db($db, $this->handle)) {
      throw new \rdbms\SQLStatementFailedException(
        'Cannot select database: '.trim(sybase_get_last_message()),
        'use '.$db,
        current(sybase_fetch_row(sybase_query('select @@error', $this->handle)))
      );
    }
    return true;
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
   * Execute any statement
   *
   * @param   string sql
   * @param   bool buffered default TRUE
   * @return  rdbms.ResultSet
   * @throws  rdbms.SQLException
   */
  protected function query0($sql, $buffered= true) {
    is_resource($this->handle) || $this->connections->establish($this);

    $tries= 1;
    retry: if (!$buffered) {
      $result= @sybase_unbuffered_query($sql, $this->handle, false);
    } else {
      $result= @sybase_query($sql, $this->handle);
    }

    if (false === $result) {
      $message= 'Statement failed: '.trim(sybase_get_last_message()).' @ '.$this->dsn->getHost();
      if (!is_resource($error= sybase_query('select @@error', $this->handle))) {
      
        // The only case selecting @@error should fail is if we receive a
        // disconnect. We could also check on the warnings stack if we can
        // find the following:
        //
        // Sybase:  Client message:  Read from SQL server failed. (severity 78)
        //
        // but that seems a bit errorprone. 
        if (0 === $this->transaction && $this->connections->retry($this, $tries)) {
          $tries++;
          goto retry;
        }
        $this->close();
        $this->transaction= 0;
        throw new \rdbms\SQLConnectionClosedException($message, $tries, $sql);
      }

      $code= current(sybase_fetch_row($error));
      switch ($code) {
        case 1205:    // Deadlock
          throw new \rdbms\SQLDeadlockException($message, $sql, $code);

        default:      // Other error
          throw new \rdbms\SQLStatementFailedException($message, $sql, $code);
      }
    } else if (true === $result) {
      return new QuerySucceeded(sybase_affected_rows($this->handle));
    } else {
      return new SybaseResultSet($result, $this->tz);
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
    $this->transaction++;
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
    $this->query('rollback transaction xp_%c', $name);
    $this->transaction--;
    return true;
  }
  
  /**
   * Commit a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function commit($name) {
    $this->query('commit transaction xp_%c', $name);
    $this->transaction--;
    return true;
  }
}