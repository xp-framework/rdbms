<?php namespace rdbms\unittest\integration;

use unittest\BeforeClass;

/**
 * Deadlock test on Sybase
 *
 * @ext  sybase_ct
 */
class SybaseDeadlockTest extends AbstractDeadlockTest {

  /**
   * Before class method: set minimun server severity;
   * otherwise server messages end up on the error stack
   * and will let the test fail (no error policy).
   *
   * @return void
   */
  #[BeforeClass]
  public static function setMinimumServerSeverity() {
    if (function_exists('sybase_min_message_severity')) {
      sybase_min_message_severity(12);
    }
  }

  /** @return string */
  protected function driverName() { return 'sybase'; }
}