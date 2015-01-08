<?php namespace rdbms;

/**
 * Represents a table's attribute
 *
 * @see   xp://rdbms.DBTable
 */
class DBTableAttribute extends \lang\Object {
  public 
    $name=        '',
    $type=        -1,
    $identity=    false,
    $nullable=    false,
    $length=      0,
    $precision=   0,
    $scale=       0;

  const
    DB_ATTRTYPE_BINARY=          0x0000,
    DB_ATTRTYPE_BIT=             0x0001,
    DB_ATTRTYPE_CHAR=            0x0002,
    DB_ATTRTYPE_DATETIME=        0x0003,
    DB_ATTRTYPE_DATETIMN=        0x0004,
    DB_ATTRTYPE_DECIMAL=         0x0005,
    DB_ATTRTYPE_DECIMALN=        0x0006,
    DB_ATTRTYPE_FLOAT=           0x0007,
    DB_ATTRTYPE_FLOATN=          0x0008,
    DB_ATTRTYPE_IMAGE=           0x0009,
    DB_ATTRTYPE_INT=             0x000A,
    DB_ATTRTYPE_INTN=            0x000B,
    DB_ATTRTYPE_MONEY=           0x000C,
    DB_ATTRTYPE_MONEYN=          0x000D,
    DB_ATTRTYPE_NCHAR=           0x000E,
    DB_ATTRTYPE_NUMERIC=         0x000F,
    DB_ATTRTYPE_NUMERICN=        0x0010,
    DB_ATTRTYPE_NVARCHAR=        0x0011,
    DB_ATTRTYPE_REAL=            0x0012,
    DB_ATTRTYPE_SMALLDATETIME=   0x0013,
    DB_ATTRTYPE_SMALLINT=        0x0014,
    DB_ATTRTYPE_SMALLMONEY=      0x0015,
    DB_ATTRTYPE_SYSNAME=         0x0016,
    DB_ATTRTYPE_TEXT=            0x0017,
    DB_ATTRTYPE_TIMESTAMP=       0x0018,
    DB_ATTRTYPE_TINYINT=         0x0019,
    DB_ATTRTYPE_VARBINARY=       0x001A,
    DB_ATTRTYPE_VARCHAR=         0x001B,
    DB_ATTRTYPE_ENUM=            0x001C,
    DB_ATTRTYPE_DATE=            0x001D;

  /**
   * Constructor
   *
   * @param   string name
   * @param   int type
   * @param   bool identity default FALSE
   * @param   bool nullable default FALSE
   * @param   int length default 0
   * @param   int precision default 0,
   * @param   int scale default 0
   */
  public function __construct(
    $name, 
    $type, 
    $identity= false, 
    $nullable= false, 
    $length= 0, 
    $precision= 0, 
    $scale= 0
  ) {
    $this->name= $name;
    $this->type= $type;
    $this->identity= $identity;
    $this->nullable= $nullable;
    $this->length= $length;
    $this->precision= $precision;
    $this->scale= $scale;
  }
  
  /**
   * Returns true if another object is equal to this table attribute
   *
   * @param   lang.Generic cmp
   * @return  bool
   */
  public function equals($cmp) {
    return $cmp instanceof self && (
      $this->name === $cmp->name &&
      $this->type === $cmp->type &&
      $this->identity === $cmp->identity &&
      $this->nullable === $cmp->nullable &&
      $this->length === $cmp->length &&
      $this->precision === $cmp->precision &&
      $this->scale === $cmp->scale
    );
  }
  
  /**
   * Returns whether this attribute is an identity field
   *
   * @return  bool
   */
  public function isIdentity() {
    return $this->identity;
  } 
  
  /**
   * Returns whether this attribute is nullable
   *
   * @return  bool
   */
  public function isNullable() {
    return $this->nullable;
  }
  
  /**
   * Returns this attribute's name
   *
   * @return  bool
   */
  public function getName() {
    return $this->name;
  }
  
  /**
   * Returns this attribute's length
   *
   * @return  int length
   */
  public function getLength() {
    return $this->length;
  }

  /**
   * Returns a textual representation of the type, e.g. 
   * DB_ATTRTYPE_VARCHAR
   *
   * @return  string type
   */
  public function getTypeString() {
    static $map= array(
      'DB_ATTRTYPE_BINARY',   
      'DB_ATTRTYPE_BIT',     
      'DB_ATTRTYPE_CHAR',    
      'DB_ATTRTYPE_DATETIME',  
      'DB_ATTRTYPE_DATETIMN',  
      'DB_ATTRTYPE_DECIMAL',   
      'DB_ATTRTYPE_DECIMALN',  
      'DB_ATTRTYPE_FLOAT',   
      'DB_ATTRTYPE_FLOATN',   
      'DB_ATTRTYPE_IMAGE',   
      'DB_ATTRTYPE_INT',     
      'DB_ATTRTYPE_INTN',    
      'DB_ATTRTYPE_MONEY',   
      'DB_ATTRTYPE_MONEYN',   
      'DB_ATTRTYPE_NCHAR',   
      'DB_ATTRTYPE_NUMERIC',   
      'DB_ATTRTYPE_NUMERICN',  
      'DB_ATTRTYPE_NVARCHAR',  
      'DB_ATTRTYPE_REAL',    
      'DB_ATTRTYPE_SMALLDATETIME',
      'DB_ATTRTYPE_SMALLINT',  
      'DB_ATTRTYPE_SMALLMONEY',
      'DB_ATTRTYPE_SYSNAME',   
      'DB_ATTRTYPE_TEXT',    
      'DB_ATTRTYPE_TIMESTAMP', 
      'DB_ATTRTYPE_TINYINT',   
      'DB_ATTRTYPE_VARBINARY', 
      'DB_ATTRTYPE_VARCHAR',
      'DB_ATTRTYPE_ENUM',
      'DB_ATTRTYPE_DATE'
    );
    return $map[$this->type];
  }
  
  /**
   * Return this attribute's type name (for XP)
   *
   * @return  string type or FALSE if unknown
   */
  public function typeName() {
    switch ($this->type) {   
      case self::DB_ATTRTYPE_BIT:
        return 'bool';
        
      case self::DB_ATTRTYPE_DATETIME:
      case self::DB_ATTRTYPE_DATETIMN:
      case self::DB_ATTRTYPE_TIMESTAMP:
      case self::DB_ATTRTYPE_SMALLDATETIME:
      case self::DB_ATTRTYPE_DATE:
        return 'util.Date';
        
      case self::DB_ATTRTYPE_BINARY:
      case self::DB_ATTRTYPE_CHAR:
      case self::DB_ATTRTYPE_IMAGE:
      case self::DB_ATTRTYPE_NCHAR:
      case self::DB_ATTRTYPE_NVARCHAR:
      case self::DB_ATTRTYPE_TEXT:
      case self::DB_ATTRTYPE_VARBINARY:
      case self::DB_ATTRTYPE_VARCHAR:
      case self::DB_ATTRTYPE_ENUM:
        return 'string';
        
      case self::DB_ATTRTYPE_DECIMAL:
      case self::DB_ATTRTYPE_DECIMALN:
      case self::DB_ATTRTYPE_NUMERIC:
      case self::DB_ATTRTYPE_NUMERICN:
        return $this->scale == 0 ? 'int' : 'float';
        
      case self::DB_ATTRTYPE_INT:
      case self::DB_ATTRTYPE_INTN:
      case self::DB_ATTRTYPE_TINYINT:
      case self::DB_ATTRTYPE_SMALLINT:
        return 'int';
        
      case self::DB_ATTRTYPE_FLOAT:
      case self::DB_ATTRTYPE_FLOATN:
      case self::DB_ATTRTYPE_MONEY:
      case self::DB_ATTRTYPE_MONEYN:
      case self::DB_ATTRTYPE_SMALLMONEY:
      case self::DB_ATTRTYPE_REAL:
        return 'float';
        
      case self::DB_ATTRTYPE_SYSNAME:
        return 'string';
    }
    
    return false;
  }
}
