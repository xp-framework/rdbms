<?php namespace rdbms;

/**
 * Indicates the connection was lost during an SQL query
 *
 * @see   rfc://0058
 */
class SQLConnectionClosedException extends SQLStatementFailedException {

  /**
   * Constructor
   *
   * @param   string $message
   * @param   int $tries
   * @param   string $sql default NULL the SQL query string sent
   * @param   int $errorcode default -1
   */
  public function __construct($message, $tries, $sql= null, $errorcode= -1) {
    if ($tries > 1) {
      $message.= ', retried '.($tries - 1).' times';
    }
    parent::__construct($message, $sql, $errorcode);
  }
}