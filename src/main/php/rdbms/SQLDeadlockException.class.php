<?php namespace rdbms;



/**
 * Indicates a deadlock occured
 * 
 * @purpose  SQL-Exception
 */
class SQLDeadlockException extends SQLStatementFailedException {

  /**
   * Return compound message of this exception.
   *
   * @return  string
   */
  public function compoundMessage() {
    return sprintf(
      "Exception %s (deadlock#%s: %s) {\n".
      "  %s\n".
      "}\n",
      nameof($this),
      $this->errorcode,
      $this->message,
      $this->sql
    );
  }
}
