<?php namespace rdbms\sqlite3;
use rdbms\sqlite\SQLiteDialect;


/**
 * helps to build functions for different SQL servers
 *
 */
class SQLite3Dialect extends SQLiteDialect {

  /**
   * register sql standard functions for a connection
   *
   * @param   db handel conn
   */
  public function registerCallbackFunctions($conn) {
    $conn->createFunction('cast', [$this, '_cast'], 2);
    $conn->createFunction('sign', [$this, '_sign'], 1);
    $conn->createFunction('dateadd', [$this, '_dateadd'], 3);
    $conn->createFunction('locate',  [$this, '_locate'], 3);
    $conn->createFunction('nullif',  [$this, '_nullif'], 2);
  }
}
