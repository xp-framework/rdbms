<?php namespace rdbms\mysqli;

use rdbms\DBConnection;
use rdbms\Transaction;
use rdbms\StatementFormatter;
use rdbms\QuerySucceeded;

/**
 * Connection to MySQL Databases
 *
 * @see      http://mysql.org/
 * @ext      mysqli
 * @test     xp://net.xp_framework.unittest.rdbms.TokenizerTest
 * @test     xp://net.xp_framework.unittest.rdbms.DBTest
 * @test     net.xp_framework.unittest.rdbms.integration.MySQLIntegrationTest
 * @purpose  Database connection
 */
class MySQLiConnection extends DBConnection {
  protected $result= null;

  static function __static() {
    if (extension_loaded('mysqli')) {
      \rdbms\DriverManager::register('mysql+i', new \lang\XPClass(__CLASS__));
    }
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) { 
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new MysqliDialect());
  }

  /**
   * Set Timeout
   *
   * @param   int timeout
   */
  public function setTimeout($timeout) {
    ini_set('mysql.connect_timeout', $timeout);
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
    if (is_object($this->handle)) return true;  // Already connected
    if (!$reconnect && (false === $this->handle)) return false;    // Previously failed connecting

    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECT, $reconnect));

    // Connect via local sockets if "." is passed. This will not work on
    // Windows with the mysqlnd extension (see PHP bug #48082: "mysql_connect
    // does not work with named pipes"). For mysqlnd, we default to mysqlx
    // anyways, so this works transparently.
    $host= $this->dsn->getHost();
    $sock= null;
    if ('.' === $host) {
      $sock= $this->dsn->getProperty('socket', null);
      if (0 === strncasecmp(PHP_OS, 'Win', 3)) {
        $host= '.';
        if (null !== $sock) $sock= substr($sock, 9);   // 9 = strlen("\\\\.\\pipe\\")
      } else {
        $host= 'localhost';
      }
    } else if ('localhost' === $host) {
      $host= '127.0.0.1';   // Force TCP/IP
    }

    $this->handle= mysqli_connect(
      ($this->flags & DB_PERSISTENT ? 'p:' : '').$host,
      $this->dsn->getUser(), 
      $this->dsn->getPassword(),
      $this->dsn->getDatabase(),
      $this->dsn->getPort(3306),
      $sock
    );

    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECTED, $reconnect));

    if (!is_object($this->handle)) {
      $e= new \rdbms\SQLConnectException('#'.mysqli_connect_errno().': '.mysqli_connect_error(), $this->dsn);
      \xp::gc(__FILE__);
      defined('HHVM_VERSION') && \xp::gc('');
      throw $e;
    }

    mysqli_set_charset($this->handle, 'utf8mb4');

    // Figure out sql_mode and update formatter's escaperules accordingly
    // - See: http://bugs.mysql.com/bug.php?id=10214
    // - Possible values: http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
    // "modes is a list of different modes separated by comma (,) characters."
    $modes= array_flip(explode(',', current(mysqli_fetch_row(mysqli_query(
      $this->handle,
      "show variables like 'sql_mode'"
    )))));
    
    // NO_BACKSLASH_ESCAPES: Disable the use of the backslash character 
    // (\) as an escape character within strings. With this mode enabled, 
    // backslash becomes any ordinary character like any other. 
    // (Implemented in MySQL 5.0.1)
    isset($modes['NO_BACKSLASH_ESCAPES']) && $this->formatter->dialect->setEscapeRules([
      '"'   => '""'
    ]);

    return parent::connect();
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() { 
    if ($this->handle && $r= mysqli_close($this->handle)) {
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
    if (!mysqli_select_db($this->handle, $db)) {
      throw new \rdbms\SQLStatementFailedException(
        'Cannot select database: '.mysqli_error($this->handle), 
        'use '.$db,
        mysqli_errno($this->handle)
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
    $i= $this->query('select last_insert_id() as xp_id')->next('xp_id');
    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::IDENTITY, $i));
    return $i;
  }

  /**
   * Retrieve number of affected rows for last query
   *
   * @return  int
   */
  protected function affectedRows() {
    return mysqli_affected_rows($this->handle);
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
    if (!is_object($this->handle)) {
      if (!($this->flags & DB_AUTOCONNECT)) throw new \rdbms\SQLStateException('Not connected');
      $c= $this->connect();
      
      // Check for subsequent connection errors
      if (false === $c) throw new \rdbms\SQLStateException('Previously failed to connect.');
    }
    
    // Clean up previous results to prevent "Commands out of sync" errors
    if (null !== $this->result) {
      mysqli_free_result($this->result);
      $this->result= null;
    }

    // Execute query
    $mode= !$buffered || $this->flags & DB_UNBUFFERED;
    $r= mysqli_query($this->handle, $sql, $mode ? MYSQLI_USE_RESULT : MYSQLI_STORE_RESULT);
    if (false === $r) {
      $code= mysqli_errno($this->handle);
      $message= 'Statement failed: '.mysqli_error($this->handle).' @ '.$this->dsn->getHost();
      switch ($code) {
        case 2006: // MySQL server has gone away
        case 2013: // Lost connection to MySQL server during query
          throw new \rdbms\SQLConnectionClosedException('Statement failed: '.$message, $sql, $code);

        case 1213: // Deadlock
          throw new \rdbms\SQLDeadlockException($message, $sql, $code);
        
        default:   // Other error
          throw new \rdbms\SQLStatementFailedException($message, $sql, $code);
      }
    } else if (true === $r) {
      return new QuerySucceeded(mysqli_affected_rows($this->handle));
    } else if ($buffered) {
      return new MySQLiResultSet($r, $this->tz);
    } else {
      $this->result= $r;
      return new MySQLiResultSet($this->result, $this->tz);
    }
  }

  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  public function begin($transaction) {
    if (!$this->query('begin')) return false;
    $transaction->db= $this;
    return $transaction;
  }
  
  /**
   * Rollback a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function rollback($name) { 
    return $this->query('rollback');
  }
  
  /**
   * Commit a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function commit($name) { 
    return $this->query('commit');
  }
}
