<?php namespace rdbms\tds;

use io\IOException;
use peer\Socket;
use rdbms\{DBConnection, DBEvent, QuerySucceeded, SQLConnectException, SQLConnectionClosedException, SQLDeadlockException, SQLStateException, SQLStatementFailedException, StatementFormatter, Transaction};
use rdbms\mssql\MsSQLDialect;

/**
 * Connection to MSSQL Databases via TDS
 *
 * @test  xp://net.xp_framework.unittest.rdbms.TokenizerTest
 * @test  xp://net.xp_framework.unittest.rdbms.DBTest
 */
abstract class TdsConnection extends DBConnection {

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) { 
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, $this->getDialect());
    $sock= new Socket($this->dsn->getHost(), $this->dsn->getPort(1433));
    $sock->setTimeout(-1);
    $this->handle= $this->getProtocol($sock);
  }
  
  /**
   * Returns dialect
   *
   * @return  rdbms.SQLDialect
   */
  protected abstract function getDialect();
  
  /**
   * Returns protocol
   *
   * @param   peer.Socket sock
   * @return  rdbms.tds.TdsProtocol
   */
  protected abstract function getProtocol($sock);

  /**
   * Connect
   *
   * @param   bool reconnect default FALSE
   * @return  bool success
   * @throws  rdbms.SQLConnectException
   */
  public function connect($reconnect= false) {
    if ($this->handle->connected) return true;                    // Already connected
    if (!$reconnect && (null === $this->handle->connected)) return false;   // Previously failed connecting

    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECT, $reconnect));
    try {
      $this->handle->connect($this->dsn->getUser(), $this->dsn->getPassword(), $this->dsn->getProperty('charset', null));
      $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));
    } catch (IOException $e) {
      $this->handle->connected= null;
      $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));
      $message= '';
      do {
        $message.= $e->getMessage().': ';
      } while ($e= $e->getCause());
      throw new SQLConnectException(substr($message, 0, -2), $this->dsn);
    }

    return parent::connect();
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() {
    $r= $this->handle->connected && $this->handle->close();
    $this->handle->connected= false;
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
    try {
      $this->handle->exec('use '.$db);
      return true;
    } catch (IOException $e) {
      throw new SQLStatementFailedException($e->getMessage());
    }
  }

  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  public function identity($field= null) {
    $i= $this->query('select @@identity as xp_id')->next('xp_id');
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::IDENTITY, $i));
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
    $this->handle->connected || $this->connections->establish($this);

    $tries= 1;
    retry: try {
      $this->handle->ready() || $this->handle->cancel();
      $result= $this->handle->query($sql);
    } catch (TdsProtocolException $e) {
      $message= $e->getMessage().' (number '.$e->number.')';
      switch ($e->number) {
        case 1205: // Deadlock
          throw new SQLDeadlockException($message, $sql, $e->number);
        
        default:   // Other error
          throw new SQLStatementFailedException($message, $sql, $e->number);
      }
    } catch (IOException $e) {
      if (0 === $this->transaction && $this->connections->retry($this, $tries)) {
        $tries++;
        goto retry;
      }
      $this->close();
      $this->transaction= 0;
      throw new SQLConnectionClosedException($e->getMessage(), $tries, $sql);
    }
    
    if (!is_array($result)) {
      return new QuerySucceeded($result);
    }

    if ($buffered) {
      return new TdsBufferedResultSet($this->handle, $result, $this->tz);
    } else {
      return new TdsResultSet($this->handle, $result, $this->tz);
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

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'(->'.$this->dsn->toString().', '.nameof($this->handle).')';
  }
}