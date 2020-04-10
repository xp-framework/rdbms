<?php namespace rdbms\unittest\mock;

use rdbms\{DBConnection, DBEvent, SQLConnectException, SQLConnectionClosedException, SQLStatementFailedException, StatementFormatter, Transaction};

/**
 * Mock database connection.
 *
 * @see      xp://rdbms.DBConnection
 * @purpose  Mock object
 */
class MockConnection extends DBConnection {
  public
    $affectedRows     = 1,
    $identityValue    = 1,
    $resultSets       = null,
    $queryError       = [],
    $connectError     = null,
    $currentResultSet = 0,
    $sql              = null;

  public
    $_connected       = null;

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) { 
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new MockDialect());
    $this->clearResultSets();
  }

  /**
   * Mock: Set ResultSet as only result set
   *
   * @param   net.xp_framework.unittest.rdbms.mock.MockResultSet resultSet
   */
  public function setResultSet($resultSet) {
    $this->queryError= [];
    $this->resultSets= [$resultSet];
    $this->currentResultSet= 0;
  }

  /**
   * Mock: Clear ResultSets
   *
   * Example:
   * <code>
   *   $conn->clearResultSets()->addResultSet(...)->addResultSet(...);
   * </code>
   *
   * @return  net.xp_framework.unittest.rdbms.mock.MockConnection this
   */
  public function clearResultSets() {
    $this->queryError= [];
    $this->resultSets= [];
    $this->currentResultSet= 0;
    return $this;
  }

  /**
   * Mock: Add ResultSet
   *
   * @param   net.xp_framework.unittest.rdbms.mock.MockResultSet resultSet
   * @return  net.xp_framework.unittest.rdbms.mock.MockConnection this
   */
  public function addResultSet($resultSet) {
    $this->queryError= [];
    $this->resultSets[]= $resultSet;
    return $this;
  }

  /**
   * Mock: Get ResultSet
   *
   * @return  net.xp_framework.unittest.rdbms.mock.MockResultSet
   */
  public function getResultSets() {
    return $this->resultSets;
  }

  /**
   * Mock: Make next query fail
   *
   * @param   int errNo
   * @param   int errMsg
   */
  public function makeQueryFail($errNo, $errMsg) {
    $this->queryError= [$errNo, $errMsg];
  }

  /**
   * Mock: Let server disconnect. This will make query() thrown
   *
   */
  public function letServerDisconnect() {
    $this->queryError= [2013];
  }

  /**
   * Mock: Make connect fail
   *
   * @param   int errMsg
   */
  public function makeConnectFail($errMsg) {
    $this->connectError= $errMsg;
  }

  /**
   * Mock: Set IdentityValue
   *
   * @param   mixed identityValue
   */
  public function setIdentityValue($identityValue) {
    $this->identityValue= $identityValue;
  }

  /**
   * Mock: Get IdentityValue
   *
   * @return  mixed
   */
  public function getIdentityValue() {
    return $this->identityValue;
  }

  /**
   * Mock: Set AffectedRows
   *
   * @param   int affectedRows
   */
  public function setAffectedRows($affectedRows) {
    $this->affectedRows= $affectedRows;
  }

  /**
   * Mock: Get AffectedRows
   *
   * @return  int
   */
  public function getAffectedRows() {
    return $this->affectedRows;
  }
  
  /**
   * Mock: Get last query
   *
   * @return  string
   */
   public function getStatement() {
     return $this->sql;
   }

  /**
   * Connect
   *
   * @param   bool reconnect default FALSE
   * @return  bool success
   * @throws  rdbms.SQLConnectException
   */
  public function connect($reconnect= false) {
    $this->sql= null;
  
    if (!$reconnect && null !== $this->_connected) return $this->_connected;

    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECT, $reconnect));
    if ($this->connectError) {
      $this->_connected= false;
      throw new SQLConnectException($this->connectError, $this->dsn);
    }

    $this->_connected= true;
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));
    return true;
  }

  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() {
    $this->_connected= null;
    return true;
  }

  /**
   * Select database
   *
   * @param   string db name of database to select
   * @return  bool success
   */
  public function selectdb($db) { 
    return true;
  }


  /**
   * Retrieve identity
   *
   * @return  mixed identity value
   */
  public function identity($field= null) {
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::IDENTITY, $this->identityValue));
    return $this->identityValue;
  }

  /**
   * Execute any statement
   *
   * @param   string sql
   * @return  rdbms.ResultSet
   * @return  rdbms.ResultSet or TRUE if no resultset was created
   * @throws  rdbms.SQLException
   */
  protected function query0($sql, $buffered= true) { 
    $this->_connected || $this->connections->establish($this);

    $tries= 1;
    retry: $this->sql= $sql;

    switch (sizeof($this->queryError)) {
      case 0: {
        if ($this->currentResultSet >= sizeof($this->resultSets)) {
          return new MockResultSet();   // Empty
        }
        
        return $this->resultSets[$this->currentResultSet++];
      }

      case 1: {   // letServerDisconnect() sets this
        $this->queryError= [];
        if (0 === $this->transaction && $this->connections->retry($this, $tries)) {
          $tries++;
          goto retry;
        }
        $this->close();
        $this->transaction= 0;
        throw new SQLConnectionClosedException('Statement failed: Read from server failed', $tries, $sql);
      }
      
      case 2: {   // makeQueryFail() sets this
        $error= $this->queryError;
        $this->queryError= [];       // Reset so next query succeeds again
        throw new SQLStatementFailedException(
          'Statement failed: '.$error[1],
          $sql, 
          $error[0]
        );
      }
    }
  }
  
  /**
   * Begin a transaction
   *
   * @param   rdbms.DBTransaction transaction
   * @return  rdbms.DBTransaction
   */
  public function begin($transaction) {
    $transaction->db= $this;
    return $transaction;
  }
  
  /**
   * Retrieve transaction state
   *
   * @param   string name
   * @return  mixed state
   */
  public function transtate($name) { }
  
  /**
   * Rollback a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function rollback($name) { }
  
  /**
   * Commit a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function commit($name) { }
}