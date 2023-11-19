<?php namespace rdbms\sqlite3;

use SQLite3, Exception;
use lang\XPClass;
use rdbms\{
  DBConnection,
  DBEvent,
  DriverManager,
  QuerySucceeded,
  SQLConnectException,
  StatementFormatter,
  SQLStateException,
  SQLStatementFailedException,
  Transaction
};

/**
 * Connection to SQLite 3.x Databases via ext/sqlite3
 *
 * Specify the path to the database file as the DSN's path - the
 * hostname property should remain empty.
 *
 * To use in-memory databases, use :memory: as path - remember to
 * urlencode its value.
 *
 * Note: SQLite is typeless. Sometimes, though, it makes sense to 
 * operate with a "real" integer instead of its string representation.
 * Typelessness is a real pain for dates (which, in other database
 * APIs, is returned as an util.Date object). 
 *
 * Therefore, this class offers a cast function which may be used
 * whithin the SQL as following:
 *
 * ```sql
 * select 
 *   cast(id, "int") id, 
 *   name, 
 *   cast(percentage, "float") percentage,
 *   cast(lastchange, "date") lastchange, 
 *   changedby
 * from 
 *   test
 * ```
 *
 * The resultset array will contain the following:
 * ```
 * key          type
 * ------------ -------------
 * id           int
 * name         string
 * percentage   float
 * lastchange   util.Date
 * changedby    string
 * ```
 *
 * @ext      sqlite3
 * @see      http://sqlite.org/
 * @see      http://php.net/sqlite3
 * @test     xp://net.xp_framework.unittest.rdbms.sqlite3.SQLite3ConnectionTest
 * @test     xp://net.xp_framework.unittest.rdbms.sqlite3.SQLite3CreationTest
 */
class SQLite3Connection extends DBConnection {

  static function __static() {
    if (extension_loaded('sqlite3')) {
      DriverManager::register('sqlite+3', new XPClass(__CLASS__));
    }
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) {
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new SQLite3Dialect());
  }
  
  /**
   * Connect
   *
   * @param   bool reconnect default FALSE
   * @return  bool success
   * @throws  rdbms.SQLConnectException
   */
  public function connect($reconnect= false) {
    if ($this->handle instanceof SQLite3) return true;  // Already connected
    if (!$reconnect && (false === $this->handle)) return false;    // Previously failed connecting

    // Sanity check: SQLite(3) works local: either loads a database from a file
    // or from memory, so connecting to remote hosts is not supported, thus
    // checked here. You may pass "localhost", though
    if ('' != $this->dsn->getHost() && '.' != $this->dsn->getHost()) {
      throw new SQLConnectException('sqlite+3:// connecting to remote database not supported', $this->dsn);
    }

    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECT, $reconnect));
    $database= (string)urldecode((string)$this->dsn->getDatabase());
    if (0 === strlen($database) || false !== strpos($database, "\0")) {
      throw new SQLConnectException('Illegal filename', $this->dsn);
    }

    try {
      $this->handle= new SQLite3($database, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
      PHP_VERSION_ID >= 90000 || $this->handle->enableExceptions(true);
    } catch (Exception $e) {
      $this->handle= false;
      throw new SQLConnectException($e->getMessage().': '.$database, $this->dsn);
    }
    
    $this->getFormatter()->dialect->registerCallbackFunctions($this->handle);
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));

    return true;
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() {
    $r= $this->handle && @$this->handle->close();
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
      'Cannot select database, not implemented in SQLite'
    );
  }

  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  public function identity($field= null) {
    if (!$this->handle instanceof SQLite3) throw new SQLStateException('Not connected');
    $i= $this->handle->lastInsertRowID();
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
    $this->handle instanceof SQLite3 || $this->connections->establish($this);

    try {
      $result= $this->handle->query($sql);
    } catch (Exception $e) {
      throw new SQLStatementFailedException(
        'Statement failed: '.$this->handle->lastErrorMsg().' @ '.$this->dsn->getDatabase(),
        $sql, 
        $this->handle->lastErrorCode()
      );
    }

    if ($result->numColumns()) {
      return new SQLite3ResultSet($result);
    } else {
      return new QuerySucceeded($this->handle->changes());
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

  /** @return string */
  public function hashCode() {
    if (PHP_VERSION_ID >= 70200) {
      $id= spl_object_id($this->handle);
    } else {
      ob_start();
      var_dump($this->handle);
      sscanf(ob_get_contents(), 'object(%*[^)])#%d', $id);
      ob_get_clean();
    }
    return 'sqlite #'.$id;
  }
}