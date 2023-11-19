<?php namespace rdbms\unittest\integration;

use unittest\Assert;
/**
 * Deadlock test on mysql
 *
 */
class MySQLDeadlockTest extends AbstractDeadlockTest {

  /** @return string */
  protected function driverName() { return 'mysql'; }

  /** @return void */
  #[After]
  public function tearDown() {
    parent::tearDown();

    // Suppress "mysql_connect(): The mysql extension is deprecated [...]"
    foreach (\xp::$errors as $file => $errors) {
      if (strstr($file, 'MySQLConnection')) {
        unset(\xp::$errors[$file]);
      }
    }
  }
}