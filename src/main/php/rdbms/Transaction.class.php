<?php namespace rdbms;
 
/**
 * Transaction
 *
 * ```php
 * use rdbms\{DriverManager, SQLException};
 *   
 * $conn= DriverManager::getConnection('sybase://user:password@server/database');
 * $tran= $conn->begin(new Transaction('test'));
 *
 * try {
 *
 *   // ... execute SQL statements
 *
 *   $tran->commit();
 * } catch (SQLException $e) {
 *   $tran->rollback();
 *   throw $e;
 * }
 * ```
 *
 * @see      xp://rdbms.DBConnection#begin
 * @purpose  Wrap a transaction
 */
class Transaction {
  public
    $name     = '',
    $db       = null;
    
  /**
   * Constructor
   *
   * @param   string name
   */
  public function __construct($name) {
    $this->name= $name;
  }
  
  /**
   * Retrieve transaction state
   *
   */
  public function getState() { 
    return $this->db->transtate($this->name);
  }
  
  /**
   * Rollback this transaction
   *
   */
  public function rollback() { 
    return $this->db->rollback($this->name);
  }
  
  /**
   * Commit this transaction
   *
   */
  public function commit() { 
    return $this->db->commit($this->name);
  }
}
