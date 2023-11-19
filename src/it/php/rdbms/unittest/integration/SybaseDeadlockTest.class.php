<?php namespace rdbms\unittest\integration;

use test\Before;

class SybaseDeadlockTest extends AbstractDeadlockTest {
  protected static $DRIVER= 'sybase';

  /**
   * Before class method: set minimun server severity;
   * otherwise server messages end up on the error stack
   * and will let the test fail (no error policy).
   *
   * @return void
   */
  #[Before]
  public static function setMinimumServerSeverity() {
    if (function_exists('sybase_min_message_severity')) {
      sybase_min_message_severity(12);
    }
  }
}