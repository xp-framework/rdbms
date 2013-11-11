<?php namespace rdbms\sqlite;

use rdbms\DBConnection;
use rdbms\Transaction;
use rdbms\StatementFormatter;


/**
 * Connection to SQLite Databases
 *
 * Note: SQLite is typeless. Sometimes, though, it makes sense to 
 * operate with a "real" integer instead of its string representation.
 * Typelessness is a real pain for dates (which, in other database
 * APIs, is returned as an util.Date object). 
 *
 * Therefore, this class offers a cast function which may be used
 * whithin the SQL as following:
 * <pre>
 *   select 
 *     cast(id, "int") id, 
 *     name, 
 *     cast(percentage, "float") percentage,
 *     cast(lastchange, "date") lastchange, 
 *     changedby
 *   from 
 *     test
 * </pre>
 *
 * The resultset array will contain the following:
 * <pre>
 *   key          type
 *   ------------ -------------
 *   id           int
 *   name         string
 *   percentage   float
 *   lastchange   util.Date
 *   changedby    string
 * </pre>
 *
 * @ext      sqlite
 * @see      http://sqlite.org/
 * @see      http://pecl.php.net/package/SQLite
 * @purpose  Database connection
 */
class SQLiteConnection extends DBConnection {

  static function __static() {
    if (extension_loaded('sqlite')) {
      \rdbms\DriverManager::register('sqlite+std', new \lang\XPClass(__CLASS__));
    }
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) {
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new SQLiteDialect());
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
    if (!($this->flags & DB_PERSISTENT)) {
      $this->handle= sqlite_open(
        urldecode($this->dsn->getDatabase()), 
        0666,
        $err
      );
    } else {
      $this->handle= sqlite_popen(
        urldecode($this->dsn->getDatabase()), 
        0666,
        $err
      );
    }

    if (!is_resource($this->handle)) {
      throw new \rdbms\SQLConnectException($err, $this->dsn);
    }
    
    $this->getFormatter()->dialect->registerCallbackFunctions($this->handle);
    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECTED, $reconnect));

    return true;
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() { 
    if ($this->handle && $r= sqlite_close($this->handle)) {
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
      'Cannot select database, not implemented in SQLite'
    );
  }

  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  public function identity($field= null) {
    $i= sqlite_last_insert_rowid($this->handle);
    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::IDENTITY, $i));
    return $i;
  }

  /**
   * Retrieve number of affected rows
   *
   * @return  int
   */
  protected function affectedRows() {
    return sqlite_changes($this->handle);
  }
  
  /**
   * Execute any statement
   *
   * @param   string sql
   * @param   bool buffered default TRUE
   * @return  rdbms.sqlite.SQLiteResultSet or FALSE to indicate failure
   * @throws  rdbms.SQLException
   */
  protected function query0($sql, $buffered= true) {
    if (!is_resource($this->handle)) {
      if (!($this->flags & DB_AUTOCONNECT)) throw new \rdbms\SQLStateException('Not connected');
      $c= $this->connect();
      
      // Check for subsequent connection errors
      if (false === $c) throw new \rdbms\SQLStateException('Previously failed to connect.');
    }
    
    if (!$buffered || $this->flags & DB_UNBUFFERED) {
      $result= sqlite_unbuffered_query($sql, $this->handle, SQLITE_ASSOC);
    } else {
      $result= sqlite_query($sql, $this->handle, SQLITE_ASSOC);
    }
    
    if (false === $result) {
      $e= sqlite_last_error($this->handle);
      throw new \rdbms\SQLStatementFailedException(
        'Statement failed: '.sqlite_error_string($e).' @ '.$this->dsn->getHost(), 
        $sql, 
        $e
      );
    }
    return sqlite_num_fields($result) ? new SQLiteResultSet($result) : true;
  }

  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  public function begin($transaction) {
    if (false === $this->query('begin transaction xp_%c', $transaction->name)) {
      return false;
    }
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
    if (false === ($r= $this->query('@@transtate as transtate'))) {
      return false;
    }
    return $r->next('transtate');
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
