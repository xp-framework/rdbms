<?php namespace rdbms;
 
class PersistentConnection extends DBConnection {
  private $conn;

  /**
   * Constructor
   *
   * @param  rdbms.DSN|rdbms.DBConnection $arg
   */
  public function __construct($arg) {
    if ($arg instanceof DBConnection) {
      $this->conn= $arg;
    } else {
      $this->conn= DriverManager::getConnection($arg);
    }
  }

  /**
   * Executes a block of SQL queries. Handles reconnecting if necessary
   *
   * @param  function(): var $block
   * @return var
   */
  private function execute($block) {
    try {
      return $block();
    } catch (SQLConnectionClosedException $e) {
      $this->conn->connect($reconnect= true);
      return $block();
    } catch (SQLConnectException $e) {
      $this->conn->close();
      throw $e;
    } catch (SQLStateException $e) {
      $this->conn->close();
      throw $e;
    }
  }

  /** @return rdbms.DSN */
  public function getDSN() { return $this->conn->getDSN(); }
  
  /** @return string */
  public function hashCode() { return 'P:'.$this->conn->hashCode(); }
  
  /** @return  string */
  public function toString() { return 'Persisent('.$this->conn->toString().')'; }

  /** @param int timeout */
  public function setTimeout($timeout) { $this->conn->setTimeout($timeout); }

  /** @return int */
  public function getTimeout() { return $this->conn->getTimeout(); }
  
  /** @param int flag */
  public function setFlag($flag) { $this->conn->setFlag($flag); }
  
  /** @return bool */
  public function hasChanged() { return $this->conn->hasChanged(); }

  /** @return rdbms.StatementFormatter */
  public function getFormatter() { return $this->conn->getFormatter(); }

  /**
   * Prepare an SQL statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return string
   */
  public function prepare($statement, ... $args) {
    return $this->conn->prepare($statement, ...$args);
  }

  /** @return bool success */
  public function connect() { return $this->conn->connect(); }  

  /** @return bool success */
  public function close() { return $this->conn->close(); }
  
  /**
   * Select database
   *
   * @param   string db name of database to select
   * @return  bool success
   */
  public function selectdb($db) {
    return $this->execute(function() use($db) {
      return $this->conn->selectdb($db);
    });
  }

  /**
   * Retrieve identity
   *
   * @return  var identity value
   */
  public function identity($field= null) {
    return $this->execute(function() use($field) {
      return $this->conn->identity($field);
    });
  }

  /**
   * Execute any statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return rdbms.ResultSet
   * @throws rdbms.SQLException
   */
  public function query($statement, ...$args) {
    return $this->execute(function() use($statement, $args) {
      return $this->conn->query($statement, ...$args);
    });
  }

  /**
   * Execute any statement
   *
   * @param  string $statement
   * @param  var... $args
   * @return rdbms.ResultSet
   * @throws rdbms.SQLException
   */
  public function open($statement, ...$args) {
    return $this->execute(function() use($statement, $args) {
      return $this->conn->open($statement, ...$args);
    });
  }

  /**
   * Begin a transaction
   *
   * @param   rdbms.Transaction transaction
   * @return  rdbms.Transaction
   */
  public function begin($transaction) {
    return $this->execute(function() use($transaction) {
      return $this->conn->begin($transaction);
    });
  }
  
  /**
   * Retrieve transaction state
   *
   * @param   string name
   * @return  var state
   */
  public function transtate($name) {
    return $this->conn->transtate($name);
  }
  
  /**
   * Rollback a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function rollback($name) {
    return $this->conn->rollback($name);
  }
  
  /**
   * Commit a transaction
   *
   * @param   string name
   * @return  bool success
   */
  public function commit($name) {
    return $this->conn->commit($name);
  } 
}
