<?php namespace rdbms\mysql;

use rdbms\DBAdapter;
use rdbms\DBTableAttribute;


/**
 * Adapter for MySQL
 *
 * @test  xp://net.xp_framework.unittest.rdbms.mysql.TableDescriptionTest
 * @see   xp://rdbms.DBAdapter
 * @see   xp://rdbms.mysql.MySQLConnection
 */
class MySQLDBAdapter extends DBAdapter {
  public static $map= [
    'varchar'    => DBTableAttribute::DB_ATTRTYPE_VARCHAR,
    'varbinary'  => DBTableAttribute::DB_ATTRTYPE_VARCHAR,
    'char'       => DBTableAttribute::DB_ATTRTYPE_CHAR,
    'int'        => DBTableAttribute::DB_ATTRTYPE_INT,
    'bigint'     => DBTableAttribute::DB_ATTRTYPE_NUMERIC,
    'mediumint'  => DBTableAttribute::DB_ATTRTYPE_SMALLINT,
    'smallint'   => DBTableAttribute::DB_ATTRTYPE_SMALLINT,
    'tinyint'    => DBTableAttribute::DB_ATTRTYPE_TINYINT,
    'bit'        => DBTableAttribute::DB_ATTRTYPE_TINYINT,
    'date'       => DBTableAttribute::DB_ATTRTYPE_DATE,
    'datetime'   => DBTableAttribute::DB_ATTRTYPE_DATETIME,
    'timestamp'  => DBTableAttribute::DB_ATTRTYPE_TIMESTAMP,
    'tinytext'   => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'mediumtext' => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'text'       => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'longtext'   => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'enum'       => DBTableAttribute::DB_ATTRTYPE_ENUM,
    'decimal'    => DBTableAttribute::DB_ATTRTYPE_DECIMAL,
    'float'      => DBTableAttribute::DB_ATTRTYPE_FLOAT,
    'double'     => DBTableAttribute::DB_ATTRTYPE_FLOAT,
    'tinyblob'   => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'blob'       => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'mediumblob' => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'longblob'   => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'time'       => DBTableAttribute::DB_ATTRTYPE_TEXT,
    'year'       => DBTableAttribute::DB_ATTRTYPE_NUMERIC,
  ];

  /**
   * Get databases
   *
   * @return  string[] databases
   */
  public function getDatabases() {
    $dbs= [];
    $q= $this->conn->query('show databases');
    while ($name= $q->next()) {
      $dbs[]= $name[key($name)];
    }
    return $dbs;
  }

  /**
   * Get tables by database
   *
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable[] array of DBTable objects
   */
  public function getTables($database= null) {
    $t= [];
    $database= $this->database($database);
    $q= $this->conn->query(
      'show tables from %c',
      $database
    );
    while ($table= $q->next()) {
      $t[]= $this->getTable($table[key($table)], $database);
    }
    return $t;
  }

  /**
   * Creates a table attribute from a "describe {table}" result
   *
   * Example:
   * <pre>
   * +-------------+--------------+------+-----+---------------------+----------------+
   * | Field       | Type         | Null | Key | Default             | Extra          |
   * +-------------+--------------+------+-----+---------------------+----------------+
   * | contract_id | int(8)       |      | PRI | NULL                | auto_increment |
   * | user_id     | int(8)       |      |     | 0                   |                |
   * | mandant_id  | int(4)       |      |     | 0                   |                |
   * | description | varchar(255) |      |     |                     |                |
   * | comment     | varchar(255) |      |     |                     |                |
   * | bz_id       | int(6)       |      |     | 0                   |                |
   * | lastchange  | datetime     |      |     | 0000-00-00 00:00:00 |                |
   * | changedby   | varchar(16)  |      |     |                     |                |
   * +-------------+--------------+------+-----+---------------------+----------------+
   * 8 rows in set (0.00 sec)
   * </pre>
   *
   * @param   [:string] record
   * @return  rdbms.DBTableAttribute
   */
  public static function tableAttributeFrom($record) {
    preg_match('#^([a-z]+)(\(([0-9,]+)\))?#', $record['Type'], $regs);
    return new DBTableAttribute(
      $record['Field'],                                         // name
      self::$map[$regs[1]],                                     // type
      false !== strpos($record['Extra'], 'auto_increment'),     // identity
      !(empty($record['Null']) || ('NO' == $record['Null'])),   // nullable
      (int)(isset($regs[3]) ? $regs[3] : 0),                    // length
      0,                                                        // precision
      0                                                         // scale
    );
  }


  /**
   * Get table by name
   *
   * @param   string table
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable a DBTable object
   */
  public function getTable($table, $database= null) {
    $t= new \rdbms\DBTable($table);
    $q= $this->conn->query('describe %c', $this->qualifiedTablename($table, $database));
    while ($record= $q->next()) {
      $t->addAttribute(self::tableAttributeFrom($record));
    }

    // Get keys
    // +----------+------------+---------------+--------------+-------------+-----------+-------------+----------+--------+---------+
    // | Table    | Non_unique | Key_name      | Seq_in_index | Column_name | Collation | Cardinality | Sub_part | Packed | Comment |
    // +----------+------------+---------------+--------------+-------------+-----------+-------------+----------+--------+---------+
    // | contract |          0 | PRIMARY       |            1 | contract_id | A         |           6 |     NULL | NULL   |         |
    // | contract |          0 | contract_id_2 |            1 | contract_id | A         |           6 |     NULL | NULL   |         |
    // | contract |          1 | contract_id   |            1 | contract_id | A         |           6 |     NULL | NULL   |         |
    // | contract |          1 | contract_id   |            2 | user_id     | A         |           6 |     NULL | NULL   |         |
    // +----------+------------+---------------+--------------+-------------+-----------+-------------+----------+--------+---------+
    $q= $this->conn->query('show keys from %c', $this->qualifiedTablename($table, $database));
    $key= null;
    while ($record= $q->next()) {
      if ($record['Key_name'] != $key) {
        $index= $t->addIndex(new \rdbms\DBIndex(
          $record['Key_name'],
          []
        ));
        $key= $record['Key_name'];
      }
      $index->unique= ('0' == $record['Non_unique']);
      $index->primary= ('PRIMARY' == $record['Key_name']);
      $index->keys[]= $record['Column_name'];
    }

    // Get foreign key constraints
    // in mysql the only way is to parse the creat statement
    $createTableString= $this->conn->query('show create table %c', $this->qualifiedTablename($table, $database))->next('Create Table');
    
    for ($i= 0; $i < strlen($createTableString); $i++) {
      switch ($createTableString[$i]) {
        case '`':
        $this->parseQuoteString($createTableString, $i, '`');
        break;

        case '"':
        $this->parseQuoteString($createTableString, $i, '"');
        break;

        case '(':
        $tableConstraints= $this->filterConstraints($this->extractParams($this->parseBracerString($createTableString, $i)));
        foreach ($tableConstraints as $tableConstraint) {
          if (strstr($tableConstraint, 'FOREIGN KEY') === false) continue;
          $t->addForeignKeyConstraint($this->parseForeignKeyString($tableConstraint));
        }
        break;
      }
    }
    return $t;
  }

  /**
   * Get full table name with database if possible
   *
   * @param   string table
   * @param   string database default NULL if omitted, uses current database
   * @return  string full table name
   */
  private function qualifiedTablename($table, $database= null) {
    $database= $this->database($database);
    if (null !== $database) return $database.'.'.$table;
    return $table;
  }

  /**
   * Get the current database
   *
   * @param   string database default NULL if omitted, uses current database
   * @return  string full table name
   */
  private function database($database= null) {
    if (null !== $database) return $database;
    return $this->conn->query('select database() as db')->next('db');
  }

  /**
   * get the foreign key object from a string
   *
   * @param   string parsestring
   * @return  rdbms.DBForeignKeyConstraint
   */
  private function parseForeignKeyString($string) {
    $constraint=   new \rdbms\DBForeignKeyConstraint();
    $quotstrings=  [];
    $bracestrings= [];
    $attributes=   [];
    $pos= 10;
    while (++$pos < strlen($string)) {
      switch ($string[$pos]) {
        case '`':
        $quotstrings[]= $this->parseQuoteString($string, $pos, '`');
        break;

        case '"':
        $quotstrings[]= $this->parseQuoteString($string, $pos, '"');
        break;

        case '(':
        $bracestrings[]= $this->parseBracerString($string, $pos);
        break;
      }
    }
    foreach ($bracestrings as $bracestring) {
      $params= $this->extractParams($bracestring);
      foreach ($params as $i => $param) $params[$i]= substr($param, 1, -1);
      $attributes[]= $params;
    }
    $constraint->setKeys(array_combine($attributes[0], $attributes[1]));
    $constraint->setName($quotstrings[0]);
    $constraint->setSource($quotstrings[1]);
    return $constraint;
  }

  /**
   * get the text inner a quotation
   *
   * @param   string parsestring
   * @param   &int position where the quoted string begins
   * @param   string quotation character
   * @return  string inner quotation
   */
  private function parseQuoteString($string, &$pos, $quot) {
    $quotedString= '';
    while ($pos++ < strlen($string)) {
      switch ($string[$pos]) {
        case $quot:
        return $quotedString;

        default:
        $quotedString.= $string[$pos];
      }
    }
    return $quotedString;
  }

  /**
   * get the text inner bracers
   *
   * @param   string parsestring
   * @param   &int position where the bracered string begins
   * @return  string inner bracers
   */
  private function parseBracerString($string, &$pos) {
    $braceredString= '';
    while ($pos++ < strlen($string)) {
      switch ($string[$pos]) {
        case ')':
        return $braceredString;
        break;

        case '(':
        $braceredString.= $string[$pos];
        $braceredString.= $this->parseBracerString($string, $pos).')';
        break;

        case '`':
        $braceredString.= $string[$pos];
        $braceredString.= $this->parseQuoteString($string, $pos, '`').'`';
        break;

        case '"':
        $braceredString.= $string[$pos];
        $braceredString.= $this->parseQuoteString($string, $pos, '"').'"';
        break;

        default:
        $braceredString.= $string[$pos];
      }
    }
    return $braceredString;
  }

  /**
   * get the single params in a paramstring
   *
   * @param   string paramstring
   * @return  string[] paramstrings
   */
  private function extractParams($string) {
    $paramArray= [];
    $paramString= '';
    $pos= 0;
    while ($pos < strlen($string)) {
      switch ($string[$pos]) {
        case ',':
        $paramArray[]= trim($paramString);
        $paramString= '';
        break;

        case '(':
        $paramString.= $string[$pos];
        $paramString.= $this->parseBracerString($string, $pos).')';
        break;

        case '`':
        $paramString.= $string[$pos];
        $paramString.= $this->parseQuoteString($string, $pos, '`').'`';
        break;

        case '"':
        $paramString.= $string[$pos];
        $paramString.= $this->parseQuoteString($string, $pos, '"').'"';
        break;

        default:
        $paramString.= $string[$pos];
      }
      $pos++;
    }
    $paramArray[]= trim($paramString);
    return $paramArray;
  }

  /**
   * filter the contraint parameters in a create table paramter string array
   *
   * @param   string[] array with parameter strings
   * @return  string[] constraint strings
   */
  private function filterConstraints($params) {
    $constraintArray= [];
    foreach ($params as $param) if ('CONSTRAINT' == substr($param, 0, 10)) $constraintArray[]= $param;
    return $constraintArray;
  }
}
