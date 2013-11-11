<?php namespace rdbms;



/**
 * Indicates the connection was lost during an SQL query
 *
 * @see      rfc://0058
 * @purpose  Exception
 */
class SQLConnectionClosedException extends SQLStatementFailedException {

}
