<?php namespace xp\rdbms;

use rdbms\DriverManager;
use util\profiling\Timer;
use util\cmd\Console;

/**
 * Runs SQL statements
 * ========================================================================
 *
 * - Execute a single SQL statement and print the results
 *   ```sh
 *   $ xp sql 'sqlite://./test.db' 'select * from test'
 *   ```
 */
class SqlRunner {

  /**
   * Runs SQL
   *
   * @param  string[] $args
   * @return int exitcode
   */
  public static function main(array $args) {
    $dsn= $args[0];
    $sql= $args[1];

    $conn= DriverManager::getConnection($dsn);
    $timer= (new Timer())->start();
    try {
      $q= $conn->query($sql);
      if ($q->isSuccess()) {
        Console::writeLinef('Query OK, %d rows affected (%.2f sec)', $q->affected(), $timer->elapsedTime());
      } else {
        $rows= 0;
        while ($record= $q->next()) {
          Console::writeLine($record);
          $rows++;
        }
        Console::writeLinef('%d rows in set (%.2f sec)', $rows, $timer->elapsedTime());
      }
      $q->close();
      Console::writeLine();
    } catch (SQLException $e) {
      Console::writeLine("\e[31m*** ", $e->compoundMessage(), "\e[0m");
    }
    return 0;
  }
}
