<?php namespace rdbms\mysqlx;

use io\IOException;
use lang\XPClass;
use peer\Socket;
use rdbms\DBConnection;
use rdbms\DBEvent;
use rdbms\DriverManager;
use rdbms\QuerySucceeded;
use rdbms\SQLConnectException;
use rdbms\SQLConnectionClosedException;
use rdbms\SQLDeadlockException;
use rdbms\SQLStateException;
use rdbms\SQLStatementFailedException;
use rdbms\StatementFormatter;
use rdbms\Transaction;
use rdbms\mysql\MysqlDialect;

/**
 * Connection to MySQL Databases
 *
 * @see   http://mysql.org/
 * @test  xp://net.xp_framework.unittest.rdbms.TokenizerTest
 * @test  xp://net.xp_framework.unittest.rdbms.DBTest
 */
class MySqlxConnection extends DBConnection {

  static function __static() {
    DriverManager::register('mysql+x', new XPClass(__CLASS__));
  }

  /**
   * Constructor
   *
   * @param   rdbms.DSN dsn
   */
  public function __construct($dsn) { 
    parent::__construct($dsn);
    $this->formatter= new StatementFormatter($this, new MysqlDialect());

    // Use local socket (unix socket on Un*x systems, named pipe on Windows)
    // if "." is supplied as hostname
    $host= $this->dsn->getHost();
    if ('.' === $host) {
      $sock= LocalSocket::forName(PHP_OS)->newInstance($this->dsn->getProperty('socket', null));
    } else {
      $sock= new Socket($host, $this->dsn->getPort(3306));
      $sock->setTimeout(-1);
    }

    $this->handle= new MySqlxProtocol($sock);
  }

  /**
   * Returns a hashcode for this connection
   *
   * Example:
   * <pre>
   *   mysqlx link #50
   * </pre>
   *
   * @return  string
   */
  public function hashCode() {
    return 'mysqlx link #'.$this->handle->hashCode();
  }

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
      $this->handle->connect($this->dsn->getUser(), $this->dsn->getPassword());
      $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));
    } catch (IOException $e) {
      $this->handle->connected= null;
      $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::CONNECTED, $reconnect));
      throw new SQLConnectException($e->getMessage(), $this->dsn);
    }

    try {

      // Figure out sql_mode and update formatter's escaperules accordingly
      // - See: http://bugs.mysql.com/bug.php?id=10214
      // - Possible values: http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
      // "modes is a list of different modes separated by comma (,) characters."
      $query= $this->handle->consume($this->handle->query("show variables like 'sql_mode'"));
      $modes= array_flip(explode(',', $query[0][1]));
    } catch (IOException $e) {
      // Ignore
    }
    
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
    $i= $this->query('select last_insert_id() as xp_id')->next('xp_id');
    $this->_obs && $this->notifyObservers(new DBEvent(DBEvent::IDENTITY, $i));
    return $i;
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
    $this->handle->connected || $this->connections->establish($this);

    $tries= 1;
    retry: try {
      $this->handle->ready() || $this->handle->cancel();
      $result= $this->handle->query($sql);
    } catch (MySqlxProtocolException $e) {
      $message= $e->getMessage().' (sqlstate '.$e->sqlstate.')';
      switch ($e->error) {
        case 2006: // MySQL server has gone away
        case 2013: // Lost connection to MySQL server during query
          if (0 === $this->transaction && $this->connections->retry($this, $tries)) {
            $tries++;
            goto retry;
          }
          $this->close();
          $this->transaction= 0;
          throw new SQLConnectionClosedException($message, $tries, $sql, $e->error);

        case 1213: // Deadlock
          throw new SQLDeadlockException($message, $sql, $e->error);
        
        default:
          throw new SQLStatementFailedException($message, $sql, $e->error);
      }
    } catch (IOException $e) {
      $this->close();
      $this->transaction= 0;
      throw new SQLConnectionClosedException($e->getMessage(), $tries, $sql, -1);
    }
    
    if (!is_array($result)) {
      return new QuerySucceeded($result);
    }

    if ($buffered) {
      return new MySqlxResultSet($this->handle, $result, $this->tz);
    } else {
      return new MySqlxBufferedResultSet($this->handle, $result, $this->tz);
    }
  }

  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  public function begin($transaction) {
    $this->query('begin');
    $this->transaction++;
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
    $this->query('rollback');
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
    $this->query('commit');
    $this->transaction--;
    return true;
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'(->'.$this->dsn->toString().', #'.$this->handle->hashCode().')';
  }
}
