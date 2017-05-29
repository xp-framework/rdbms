<?php namespace xp\rdbms;

use rdbms\DriverManager;
use rdbms\DefaultDrivers;
use util\profiling\Timer;
use util\cmd\Console;
use io\streams\Streams;
use lang\XPClass;

/**
 * Runs SQL statements
 * ========================================================================
 *
 * - Execute a single SQL statement and print the results
 *   ```sh
 *   $ xp sql 'sqlite://./test.db' 'select * from test'
 *   ```
 * - Read SQL statement from standard input using "-"
 *   ```sh
 *   $ cat statement.sql | xp sql 'sqlite://./test.db' -
 *   ```
 *
 * Invoking without arguments shows a list of available drivers.
 */
class SqlRunner {

  /**
   * Shows drivers
   *
   * @param  rdbms.DriverImplementationsProvider $provider
   * @return int
   */
  private function drivers($provider) {
    Console::writeLine("\e[33m@", nameof($provider), "\e[0m");
    Console::writeLine("\e[1mAvailable drivers\e[0m");
    Console::writeLine(str_repeat('═', 72));
    Console::writeLine();

    // Load all implementations
    foreach ($provider->drivers() as $driver) {
      foreach ($provider->implementationsFor($driver) as $impl) {
        XPClass::forName($impl);
      }
    }

    // Iterate over registered
    $registered= DriverManager::getInstance();
    foreach ($registered->drivers as $driver => $impl) {
      Console::writeLine("\e[33;1m>\e[0m \e[35;1m", $driver, "\e[0m: ", $impl->getName());

      $comment= $impl->getComment();
      Console::writeLine('  ', substr($comment, 0, strcspn($comment, "\n")));
      Console::writeLine();
    }
    return 0;
  }

  /**
   * Starts an interactive SQL shell
   *
   * @param  string $dsn
   * @return int
   */
  private function interactive($dsn) {
    Console::$err->writeLine('Not yet implemented');
    return 255;
  }

  /**
   * Executes SQL statements; stops on first statement causing an error.
   *
   * @param  string $dsn
   * @param  string... $statements
   * @return int
   */
  private function execute($dsn, ...$statements) {
    $conn= DriverManager::getConnection($dsn);
    $timer= new Timer();

    foreach ($statements as $statement) {
      if ('-' === $statement) {
        $sql= Streams::readAll(Console::$in->getStream());
      } else {
        $sql= $statement;
      }

      try {
        $q= $conn->query($sql);
        if ($q->isSuccess()) {
          Console::$err->writeLinef('Query OK, %d rows affected (%.2f sec)', $q->affected(), $timer->elapsedTime());
        } else {
          $rows= 0;
          while ($record= $q->next()) {
            Console::writeLine($record);
            $rows++;
          }
          Console::$err->writeLinef('%d rows in set (%.2f sec)', $rows, $timer->elapsedTime());
        }
        $q->close();
      } catch (SQLException $e) {
        Console::$err->writeLine("\e[31m*** ", $e->compoundMessage(), "\e[0m");
        return 1;
      }
    }
    return 0;
  }

  /**
   * Runs SQL
   *
   * @param  string[] $args
   * @return int exitcode
   */
  public static function main(array $args) {
    switch (sizeof($args)) {
      case 0: return self::drivers(new DefaultDrivers());
      case 1: return self::interactive($args[0]);
      default: return self::execute($args[0], array_slice($args, 1));
    }
  }
}
