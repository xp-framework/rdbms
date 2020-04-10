<?php namespace rdbms;



/**
 * Default driver preferences for driver manager
 *
 * @see   xp://rdbms.DriverImplementationsProvider
 */
class DefaultDrivers extends DriverImplementationsProvider {
  protected static $impl= [];

  static function __static() {

    // MySQL support: Use mysqli extension by default, mysql otherwise. Never use mysqlnd!
    if (extension_loaded('mysqlnd')) {
      self::$impl['mysql']= ['rdbms.mysqlx.MySqlxConnection', 'rdbms.mysqli.MySQLiConnection', 'rdbms.mysql.MySQLConnection'];
    } else if (extension_loaded('mysqli')) {
      self::$impl['mysql']= ['rdbms.mysqli.MySQLiConnection', 'rdbms.mysql.MySQLConnection', 'rdbms.mysqlx.MySqlxConnection'];
    } else if (extension_loaded('mysql')) {
      self::$impl['mysql']= ['rdbms.mysql.MySQLConnection', 'rdbms.mysqli.MySQLiConnection', 'rdbms.mysqlx.MySqlxConnection'];
    } else {
      self::$impl['mysql']= ['rdbms.mysqlx.MySqlxConnection', 'rdbms.mysqli.MySQLiConnection', 'rdbms.mysql.MySQLConnection'];
    }

    // Sybase support: Prefer sybase_ct over mssql
    if (extension_loaded('sybase_ct')) {
      self::$impl['sybase']= ['rdbms.sybase.SybaseConnection', 'rdbms.mssql.MsSQLConnection', 'rdbms.tds.SybasexConnection'];
    } else {
      self::$impl['sybase']= ['rdbms.mssql.MsSQLConnection', 'rdbms.sybase.SybaseConnection', 'rdbms.tds.SybasexConnection'];
    }

    // MSSQL support: Prefer SQLsrv from Microsoft over mssql 
    if (extension_loaded('sqlsrv')) {
      self::$impl['mssql']= ['rdbms.sqlsrv.SqlSrvConnection', 'rdbms.mssql.MsSQLConnection', 'rdbms.tds.MsSQLxConnection'];
    } else {
      self::$impl['mssql']= ['rdbms.mssql.MsSQLConnection', 'rdbms.sqlsrv.SqlSrvConnection', 'rdbms.tds.MsSQLxConnection'];
    }

    // PostgreSQL support
    self::$impl['pgsql']= ['rdbms.pgsql.PostgreSQLConnection'];

    // SQLite support
    self::$impl['sqlite']= ['rdbms.sqlite3.SQLite3Connection'];

    // Interbase support
    self::$impl['ibase']= ['rdbms.ibase.InterBaseConnection'];
  }

  /** @return string[] */
  public function drivers() { return array_keys(self::$impl); }

  /**
   * Returns an array of class names implementing a given driver
   *
   * @param   string driver
   * @return  string[] implementations
   */
  public function implementationsFor($driver) {
    return isset(self::$impl[$driver]) ? self::$impl[$driver] : parent::implementationsFor($driver);
  }
}