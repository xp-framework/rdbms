<?php namespace rdbms\sqlite;

use rdbms\DBAdapter;
use rdbms\DBTableAttribute;

/**
 * Adapter for SQLite
 *
 * @see   http://sqlite.org/pragma.html
 * @see   xp://rdbms.DBAdapter
 * @see   xp://rdbms.mysql.MySQLConnection
 */
class SQLiteDBAdapter extends DBAdapter {
  protected
    $map    = [];
    
  /**
   * Constructor
   *
   * @param   rdbms.DBConnection conn database connection
   */
  public function __construct($conn) {
    $this->map= [
      'varchar'    => DBTableAttribute::DB_ATTRTYPE_VARCHAR,
      'char'       => DBTableAttribute::DB_ATTRTYPE_CHAR,
      'int'        => DBTableAttribute::DB_ATTRTYPE_INT,
      'integer'    => DBTableAttribute::DB_ATTRTYPE_INT,
      'bigint'     => DBTableAttribute::DB_ATTRTYPE_NUMERIC,
      'mediumint'  => DBTableAttribute::DB_ATTRTYPE_SMALLINT,
      'smallint'   => DBTableAttribute::DB_ATTRTYPE_SMALLINT,
      'tinyint'    => DBTableAttribute::DB_ATTRTYPE_TINYINT,
      'date'       => DBTableAttribute::DB_ATTRTYPE_DATE,
      'datetime'   => DBTableAttribute::DB_ATTRTYPE_DATETIME,
      'timestamp'  => DBTableAttribute::DB_ATTRTYPE_TIMESTAMP,
      'mediumtext' => DBTableAttribute::DB_ATTRTYPE_TEXT,
      'text'       => DBTableAttribute::DB_ATTRTYPE_TEXT,
      'enum'       => DBTableAttribute::DB_ATTRTYPE_ENUM,
      'decimal'    => DBTableAttribute::DB_ATTRTYPE_DECIMAL,
      'float'      => DBTableAttribute::DB_ATTRTYPE_FLOAT
    ];
    parent::__construct($conn);
  }
 
  /**
   * Retrieve list of all databases
   *
   * @return  string[]
   */
  public function getDatabases() {
    $dbs= [];
    $q= $this->conn->query('pragma database_list');
    while ($name= $q->next('file')) {
      $dbs[]= basename($name);
    }
    return $dbs;
  }
  
  /**
   * Retrive list of all tables
   *
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable[] array of DBTable objects
   */
  public function getTables($database= null) {
    $t= [];
    $q= $this->conn->query('select tbl_name from sqlite_master where type= "table"');
    while ($table= $q->next('tbl_name')) {
      $t[]= $this->getTable($table);
    }
    
    return $t;
  }
  
  /**
   * Retrieve type from column description
   *
   * @param   string
   * @return  string
   */
  protected function typeOf($desc) {
    if (2 == sscanf($desc, '%[^(](%d)', $type, $length)) {
      return strtolower($type);
    }
    
    return strtolower($desc);
  }
  
  /**
   * Retrieve length from type
   *
   * @param   string desc
   * @return  int
   */
  protected function lengthOf($desc) {
    if (2 == sscanf($desc, '%[^(](%d)', $type, $length)) {
      return $length;
    }
    
    return 0;
  }
  
  /**
   * Get table information by name
   *
   * @param   string table
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable
   */
  public function getTable($table, $database= null) {
    $t= new \rdbms\DBTable($table);

    $primaryKey= [];
    $q= $this->conn->query('pragma table_info(%s)', $table);
    
    while ($record= $q->next()) {
      $t->addAttribute(new \rdbms\DBTableAttribute(
        $record['name'],
        $this->map[$this->typeOf($record['type'])],
        $record['pk'],
        !$record['notnull'],
        $this->lengthOf($record['type']),
        0,
        0
      ));
      
      if ($record['pk']) $primaryKey[$record['name']]= true;
    }
    
    $q= $this->conn->query('pragma index_list(%s)', $table);
    while ($index= $q->next()) {
      $dbindex= $t->addIndex(new \rdbms\DBIndex(
        $index['name'],
        []
      ));
      
      $dbindex->unique= (bool)$index['unique'];

      $qi= $this->conn->query('pragma index_info(%s)', $index['name']);
      while ($column= $qi->next('name')) { $dbindex->keys[]= $column; }
      
      // Find out if this index covers exactly the primary key
      $dbindex->primary= true;
      foreach ($dbindex->keys as $k) {
        if (!isset($primaryKey[$k])) {
          $dbindex->primary= false;
          break;
        }
      }
    }
    
    return $t;
  }
}
