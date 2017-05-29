<?php namespace rdbms;



/**
 * Abstract base class for a database adapter for DBTable operations
 * 
 * @see      xp://rdbms.DBTable
 * @purpose  RDBMS reflection
 */  
abstract class DBAdapter {
  public
    $conn=  null;
    
  /**
   * Constructor
   *
   * @param   rdbms.DBConnection conn a database connection
   */
  public function __construct($conn) {
    $this->conn= $conn;
  }

  /**
   * Get a table in the current database
   *
   * @param   string name
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable
   */    
  public abstract function getTable($name, $database= null);

  /**
   * Get tables by database
   *
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable[] array of DBTable objects
   */
  public abstract function getTables($database= null);
  
  /**
   * Get databaases
   *
   * @return  string[]
   */    
  public abstract function getDatabases();
}
