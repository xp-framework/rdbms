<?php namespace rdbms\sybase;

use rdbms\DBAdapter;
use rdbms\DBTableAttribute;


/**
 * Adapter for sybase
 *
 * @see   xp://rdbms.DBAdapter
 * @see   xp://rdbms.sybase.SybaseConnection
 */
class SybaseDBAdapter extends DBAdapter {
  public
    $map= [];
    
  /**
   * Constructor
   *
   * @param   Object conn database connection
   */
  public function __construct($conn) {
    $this->map= [
      'binary'        => DBTableAttribute::DB_ATTRTYPE_BINARY,
      'bit'           => DBTableAttribute::DB_ATTRTYPE_BIT,
      'char'          => DBTableAttribute::DB_ATTRTYPE_CHAR,
      'datetime'      => DBTableAttribute::DB_ATTRTYPE_DATETIME,
      'datetimn'      => DBTableAttribute::DB_ATTRTYPE_DATETIMN,
      'decimal'       => DBTableAttribute::DB_ATTRTYPE_DECIMAL,
      'decimaln'      => DBTableAttribute::DB_ATTRTYPE_DECIMALN,
      'float'         => DBTableAttribute::DB_ATTRTYPE_FLOAT,
      'floatn'        => DBTableAttribute::DB_ATTRTYPE_FLOATN,
      'image'         => DBTableAttribute::DB_ATTRTYPE_IMAGE,
      'uint'          => DBTableAttribute::DB_ATTRTYPE_INT,
      'int'           => DBTableAttribute::DB_ATTRTYPE_INT,
      'intn'          => DBTableAttribute::DB_ATTRTYPE_INTN,
      'money'         => DBTableAttribute::DB_ATTRTYPE_MONEY,
      'moneyn'        => DBTableAttribute::DB_ATTRTYPE_MONEYN,
      'nchar'         => DBTableAttribute::DB_ATTRTYPE_NCHAR,
      'numeric'       => DBTableAttribute::DB_ATTRTYPE_NUMERIC,
      'numericn'      => DBTableAttribute::DB_ATTRTYPE_NUMERICN,
      'nvarchar'      => DBTableAttribute::DB_ATTRTYPE_NVARCHAR,
      'real'          => DBTableAttribute::DB_ATTRTYPE_REAL,
      'smalldatetime' => DBTableAttribute::DB_ATTRTYPE_SMALLDATETIME,
      'smallint'      => DBTableAttribute::DB_ATTRTYPE_SMALLINT,
      'smallmoney'    => DBTableAttribute::DB_ATTRTYPE_SMALLMONEY,
      'sysname'       => DBTableAttribute::DB_ATTRTYPE_SYSNAME,
      'longsysname'   => DBTableAttribute::DB_ATTRTYPE_SYSNAME,
      'text'          => DBTableAttribute::DB_ATTRTYPE_TEXT,
      'timestamp'     => DBTableAttribute::DB_ATTRTYPE_TIMESTAMP,
      'tinyint'       => DBTableAttribute::DB_ATTRTYPE_TINYINT,
      'unichar'       => DBTableAttribute::DB_ATTRTYPE_CHAR,
      'univarchar'    => DBTableAttribute::DB_ATTRTYPE_VARCHAR,
      'varbinary'     => DBTableAttribute::DB_ATTRTYPE_VARBINARY,
      'varchar'       => DBTableAttribute::DB_ATTRTYPE_VARCHAR,
    ];
    parent::__construct($conn);
  }
  
  /**
   * Get databases
   *
   * @return  string[] databases
   */
  public function getDatabases() {
    $dbs= [];
    $q= $this->conn->query('select name from master..sysdatabases');
    while ($name= $q->next('name')) {
      $dbs[]= $name;
    }
    return $dbs;
  }

  /**
   * Creates temporary table needed for fetching table indexes
   *
   */
  protected function prepareTemporaryIndexesTable() {
    $this->conn->query('create table #indexes (
      keys varchar(200),
      name varchar(28),
      number int,
      status int
    )');
  }

  /**
   * Get indexes for a given table. Expects a temporary table to exist.
   *
   * @param   string table thee table's name
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable
   */    
  protected function dbTableObjectFor($table, $database= null) {
    $t= new \rdbms\DBTable($table);
    
    // Get the table's attributes
    $q= $this->conn->query('
      select 
        c.name, 
        t.name as type, 
        c.status,
        c.length, 
        c.prec, 
        c.scale
      from 
        syscolumns c,
        systypes t 
      where 
        c.id= object_id(%s)
        and t.type = c.type
        and t.usertype < 100 
        and t.name not in ("sysname", "nchar", "nvarchar")
    ', $this->qualifiedTablename($table, $database));
    while ($record= $q->next()) {
      // Known bits of status column:
      // 0x08 => NULLable
      // 0x80 => identity column
      $t->addAttribute(new DBTableAttribute(
        $record['name'], 
        $this->map[$record['type']],
        ($record['status'] & 0x80), 
        ($record['status'] & 8), 
        $record['length'], 
        $record['prec'], 
        $record['scale']
      ));
    }
    unset($q);
      
    // This query is taken in part from sp_help (part of core sps from
    // SQL Server/11.0.3.3 ESD#6/P-FREE/Linux Intel/Linux 2.2.14 
    // i686/1/OPT/Fri Mar 17 15:45:30 CET 2000)
    $q= $this->conn->query('
      declare @i int
      declare @id int
      declare @last int
      declare @keys varchar(200)
      declare @key varchar(48)
      declare @obj varchar(48)

      delete from #indexes  

      select @obj= %s
      select @id= min(indid) from sysindexes where id= object_id(@obj)

      while @id is not NULL
      begin
        set nocount on
        select @keys= "", @i= 1
        while (@i <= 16) begin
        select @key= index_col(@obj, @id, @i) 
        if @key is NULL begin
          goto done
        end
        if @i > 1 begin
          select @keys= @keys + ","
        end 
        select @keys= @keys + @key
        select @i= @i + 1  
        end
        done:
        set nocount off


        insert #indexes select 
        @keys,
        i.name,
        v.number,
        i.status
        from 
        master.dbo.spt_values v, sysindexes i
        where 
        i.status & v.number = v.number
        and v.type = "I"
        and i.id = object_id(@obj)
        and i.indid = @id 

        select @last = @id
        select @id = min(indid) from sysindexes where id = object_id(@obj) and indid > @last
      end

      select * from #indexes', 
      $this->qualifiedTablename($table, $database)
    );
    
    $keys= null;
    while ($record= $q->next()) {

      // Ignore records which look like this - they contain only the table name
      // array(4) {
      //   ["keys"]   => string(1) " "
      //   ["name"]   => string(8) "software"
      //   ["number"] => int(0)
      //   ["status"] => int(0)
      // }
      if ('' === ($k= trim($record['keys']))) continue;

      if ($keys != $k) {
        $index= $t->addIndex(new \rdbms\DBIndex(
          $record['name'],
          explode(',', $k)
        ));
        $keys= $k;
      }
      if (2 == $record['number']) $index->unique= true;
      if ($record['status'] & 2048) $index->primary= true;
    }

    // Get foreign key constraints
    $sp_helpconstraint= $this->conn->query('sp_helpconstraint %s, detail', $this->qualifiedTablename($table, $database));
    if (!$sp_helpconstraint instanceof \rdbms\ResultSet) return $t;

    while ($db_constraint= $sp_helpconstraint->next()) {
      if (!isset($db_constraint['type']) || !isset($db_constraint['definition'])) continue;

      if ('referential constraint' != $db_constraint['type']) continue;
      if (0 !== strpos($db_constraint['definition'], $table.' ')) continue;
      $t->addForeignKeyConstraint($this->parseForeignKey($db_constraint));
    }
    $sp_helpconstraint->close();

    return $t;
  }
  
  /**
   * Drops temporary created by prepareTemporaryIndexesTable()
   *
   */
  protected function dropTemporaryIndexesTable() {
    $this->conn->query('drop table #indexes');
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
    try {
      $this->prepareTemporaryIndexesTable();
      
      // Get all tables
      $q= $this->conn->query('
        select 
          o.name 
        from 
          %c..sysobjects o 
        where 
          o.type = "U"          -- User table
          and o.name not in (   -- Replication tables
            "rs_threads", 
            "rs_dbversion", 
            "rs_lastcommit", 
            "rs_ticket_history", 
            "ticket_detail", 
            "ticket_hist", 
            "ticket_result"
          )
        ',
        $database
      );
      while ($record= $q->next()) {
        $t[]= $this->dbTableObjectFor($record['name'], $database);
      }
    } finally {
      $this->dropTemporaryIndexesTable();
    }
    
    return $t;
  }

  /**
   * Get table by name
   *
   * @param   string table
   * @param   string database default NULL if omitted, uses current database
   * @return  rdbms.DBTable a DBTable object
   */
  public function getTable($table, $database= null) {
    try {
      $this->prepareTemporaryIndexesTable();
      $t= $this->dbTableObjectFor($table, $database);
    } catch (\rdbms\SQLException $e) {
      unset($t);
    } ensure($e); {
      $this->dropTemporaryIndexesTable();
      if ($e) throw $e;
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
    if (null !== $database) return $database.'..'.$table;
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
    return $this->conn->query('select db_name() as db')->next('db');
  }

  /**
   * get the foreign key object from a db result array
   *
   * @param   string[] dbresult array
   * @return  rdbms.DBForeignKeyConstraint
   */
  private function parseForeignKey($db_constraint) {
    $cstring= $db_constraint['definition'];
    $bracestrings= $this->subBracerString($cstring);
    $strings= explode(' ', $cstring);
    $attributes= [];
    foreach ($bracestrings as $bracestring) $attributes[]= $this->extractParams($bracestring);

    $constraint= new \rdbms\DBForeignKeyConstraint();
    $constraint->setSource($strings[5]);
    $constraint->setName($db_constraint['name']);
    $constraint->setKeys(array_combine($attributes[0], $attributes[1]));
    return $constraint;
  }

  /**
   * cut bracered strings out of strings
   *
   * @param   string parsestring
   * @return  string[] inner bracers
   */
  private function subBracerString(&$string) {
    $rstring= '';
    $braceredString= [];
    $pos= 0;
    while ($pos < strlen($string)) {
      switch ($string{$pos}) {
        case '(':
        $braceredString[]= $this->parseBracerString($string, $pos);
        break;
        
        default:
        $rstring.= $string{$pos};
      }
      $pos++;
    }
    $string= $rstring;
    return $braceredString;
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
      switch ($string{$pos}) {
        case ')':
        return $braceredString;
        break;
        
        case '(':
        $braceredString.= $string{$pos};
        $braceredString.= $this->parseBracerString($string, $pos).')';
        break;
        
        default:
        $braceredString.= $string{$pos};
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
      switch ($string{$pos}) {
        case ',':
        $paramArray[]= trim($paramString);
        $paramString= '';
        break;
        
        case '(':
        $paramString.= $string{$pos};
        $paramString.= $this->parseBracerString($string, $pos).')';
        break;

        default:
        $paramString.= $string{$pos};
      }
      $pos++;
    }
    $paramArray[]= trim($paramString);
    return $paramArray;
  }
}
