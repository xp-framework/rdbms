<?php namespace rdbms\mysql;

use rdbms\DBConnection;
use rdbms\Transaction;
use rdbms\StatementFormatter;


/**
 * Connection to MySQL Databases
 *
 * @see      http://mysql.org/
 * @ext      mysql
 * @test     xp://net.xp_framework.unittest.rdbms.TokenizerTest
 * @test     xp://net.xp_framework.unittest.rdbms.DBTest
 * @test     net.xp_framework.unittest.rdbms.integration.MySQLIntegrationTest
 * @purpose  Database connection
 */
class MySQLConnection extends DBConnection {

  static function __static() {
    \rdbms\DriverManager::register('mysql+std', new \lang\XPClass(__CLASS__));
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) { 
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new MysqlDialect());
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
    if (is_resource($this->handle)) return true;  // Already connected
    if (!$reconnect && (false === $this->handle)) return false;    // Previously failed connecting

    // Connect via local sockets if "." is passed. This will not work on
    // Windows with the mysqlnd extension (see PHP bug #48082: "mysql_connect
    // does not work with named pipes"). For mysqlnd, we default to mysqlx
    // anyways, so this works transparently.
    $host= $this->dsn->getHost();
    $ini= null;
    if ('.' === $host) {
      $sock= $this->dsn->getProperty('socket', null);
      if (0 === strncasecmp(PHP_OS, 'Win', 3)) {
        $connect= '.';
        if (null !== $sock) {
          $ini= ini_set('mysql.default_socket');
          ini_set('mysql.default_socket', substr($sock, 9)); // 9 = strlen("\\\\.\\pipe\\")
        }
      } else {
        $connect= null === $sock ? 'localhost' : ':'.$sock;
      }
    } else if ('localhost' === $host) {
      $connect= '127.0.0.1:'.$this->dsn->getPort(3306);   // Force TCP/IP
    } else {
      $connect= $host.':'.$this->dsn->getPort(3306);
    }

    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECT, $reconnect));
    if ($this->flags & DB_PERSISTENT) {
      $this->handle= mysql_pconnect(
        $connect,
        $this->dsn->getUser(), 
        $this->dsn->getPassword()
      );
    } else {
      $this->handle= mysql_connect(
        $connect,
        $this->dsn->getUser(), 
        $this->dsn->getPassword(),
        ($this->flags & DB_NEWLINK)
      );
    }
    
    $this->_obs && $this->notifyObservers(new \rdbms\DBEvent(\rdbms\DBEvent::CONNECTED, $reconnect));
    $ini && ini_set('mysql.default_socket', $ini);
    if (!is_resource($this->handle)) {
      $e= new \rdbms\SQLConnectException('#'.mysql_errno().': '.mysql_error(), $this->dsn);
      \xp::gc(__FILE__);
      throw $e;
    }

    mysql_query('set names LATIN1', $this->handle);

    // Figure out sql_mode and update formatter's escaperules accordingly
    // - See: http://bugs.mysql.com/bug.php?id=10214
    // - Possible values: http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
    // "modes is a list of different modes separated by comma (,) characters."
    $modes= array_flip(explode(',', current(mysql_fetch_row(mysql_query(
      "show variables like 'sql_mode'", 
      $this->handle
    )))));
    
    // NO_BACKSLASH_ESCAPES: Disable the use of the backslash character 
    // (\) as an escape character within strings. With this mode enabled, 
    // backslash becomes any ordinary character like any other. 
    // (Implemented in MySQL 5.0.1)
    isset($modes['NO_BACKSLASH_ESCAPES']) && $this->formatter->dialect->setEscapeRules(array(
      '"'   => '""'
    ));

    return parent::connect();
  }
  
  /**
   * Disconnect
   *
   * @return  bool success
   */
  public function close() { 
    if ($this->handle && $r= mysql_close($this->handle)) {
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
    if (!mysql_select_db($db, $this->handle)) {
      throw new \rdbms\SQLStatementFailedException(
        'Cannot select database: '.mysql_error($this->handle), 
        'use '.$db,
        mysql_errno($this->handle)
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
    return mysql_affected_rows($this->handle);
  }    
  
  /**
   * Execute any statement
   *
   * @param   string sql
   * @param   bool buffered default TRUE
   * @return  rdbms.ResultSet or TRUE if no resultset was created
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
      $result= @mysql_unbuffered_query($sql, $this->handle);
    } else {
      $result= @mysql_query($sql, $this->handle);
    }
    
    if (false === $result) {
      $code= mysql_errno($this->handle);
      $message= 'Statement failed: '.mysql_error($this->handle).' @ '.$this->dsn->getHost();
      switch ($code) {
        case 2006: // MySQL server has gone away
        case 2013: // Lost connection to MySQL server during query
          throw new \rdbms\SQLConnectionClosedException('Statement failed: '.$message, $sql, $code);

        case 1213: // Deadlock
          throw new \rdbms\SQLDeadlockException($message, $sql, $code);
        
        default:   // Other error
          throw new \rdbms\SQLStatementFailedException($message, $sql, $code);
      }
    }
    
    return (true === $result
      ? $result
      : new MySQLResultSet($result, $this->tz)
    );
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
