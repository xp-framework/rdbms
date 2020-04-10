<?php namespace rdbms\pgsql;

use lang\XPClass;
use rdbms\{DBConnection, DBEvent, DriverManager, QuerySucceeded, SQLConnectException, SQLConnectionClosedException, SQLDeadlockException, SQLStatementFailedException, StatementFormatter, Transaction};

/**
 * Connection to PostgreSQL Databases via ext/pgsql
 *
 * @see   http://www.postgresql.org/
 * @see   http://www.freebsddiary.org/postgresql.php
 * @ext   pgsql
 * @test  xp://rdbms.unittest.integration.PostgreSQLIntegrationTest
 */
class PostgreSQLConnection extends DBConnection {
  protected $result= null;

  static function __static() {
    if (extension_loaded('pgsql')) {
      DriverManager::register('pgsql+std', new XPClass(__CLASS__));
    }
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) {
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new PostgreSQLDialect());
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

    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECT, $reconnect));

    // Build connection string. In PostgreSQL, a dbname must _always_
    // be specified.
    $cs= 'dbname='.$this->dsn->getDatabase();
    if ($this->dsn->getHost()) $cs.= ' host='.$this->dsn->getHost();
    if ($this->dsn->getPort()) $cs.= ' port='.$this->dsn->getPort();
    if ($this->dsn->getUser()) $cs.= ' user='.$this->dsn->getUser();
    if ($this->dsn->getPassword()) $cs.= ' password='.$this->dsn->getPassword();

    $this->handle= pg_connect($cs, PGSQL_CONNECT_FORCE_NEW);
    if (!is_resource($this->handle)) {
      $e= new SQLConnectException(rtrim(pg_last_error()), $this->dsn);
      \xp::gc(__FILE__);
      throw $e;
    }

    $this->pid= pg_get_pid($this->handle);
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));
    return true;
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() {
    $r= $this->handle && pg_close($this->handle);
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
    throw new SQLStatementFailedException(
      'Cannot select database, not implemented in PostgreSQL'
    );
  }

  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  public function identity($field= null) {
    $q= $this->query('select currval(%s) as id', $field);
    $id= $q ? $q->next('id') : null;
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::IDENTITY, $id));
    return $id;
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
    retry: $success= pg_send_query($this->handle, $sql);
    if (!$success) {
      $message= 'Statement failed: '.rtrim(pg_last_error($this->handle)).' @ '.$this->dsn->getHost();
      if (PGSQL_CONNECTION_OK !== pg_connection_status($this->handle)) {
        if (0 === $this->transaction && $this->connections->retry($this, $tries)) {
          $tries++;
          goto retry;
        }
        $this->close();
        $this->transaction= 0;
        throw new SQLConnectionClosedException($message, $tries, $sql);
      } else {
        throw new SQLStatementFailedException($message, $sql);
      }
    }
    
    $this->result= pg_get_result($this->handle);
    switch ($status= pg_result_status($this->result, PGSQL_STATUS_LONG)) {
      case PGSQL_FATAL_ERROR: case PGSQL_BAD_RESPONSE: {
        $code= pg_result_error_field($this->result, PGSQL_DIAG_SQLSTATE);
        $message= 'Statement failed: '.pg_result_error_field($this->result, PGSQL_DIAG_MESSAGE_PRIMARY).' @ '.$this->dsn->getHost();
        switch ($code) {
          case '57P01':
            if (0 === $this->transaction && $this->connections->retry($this, $tries)) {
              $tries++;
              goto retry;
            }
            $this->close();
            $this->transaction= 0;
            throw new SQLConnectionClosedException($message, $tries, $sql, $code);

          case '40P01':
            throw new SQLDeadlockException($message, $sql, $code);

          default:
            throw new SQLStatementFailedException($message, $sql, $code);
        }
      }
      
      case PGSQL_COMMAND_OK: {
        return new QuerySucceeded(pg_affected_rows($this->result));
      }
      
      default: {
        return new PostgreSQLResultSet($this->result, $this->tz);
      }
    }
  }
  
  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  public function begin($transaction) {
    $this->query('begin transaction');
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
    return -1;
  }
  
  /**
   * Rollback a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function rollback($name) {
    $this->query('rollback transaction');
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
    $this->query('commit transaction');
    $this->transaction--;
    return true;
  }

  /**
   * Returns a hashcode for this connection
   *
   * Example:
   * <pre>
   *   pgsql link #4718
   * </pre>
   *
   * @return  string
   */
  public function hashCode() {
    return get_resource_type($this->handle).' #'.$this->pid;
  }
}