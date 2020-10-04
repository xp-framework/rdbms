<?php namespace rdbms\unittest\mysql;

use rdbms\mysqlx\MySqlPassword;
use unittest\Test;
use util\Bytes;

/**
 * TestCase
 *
 * @see     xp://rdbms.mysqlx.MySqlPassword
 */
class MySqlPasswordTest extends \unittest\TestCase {

  #[Test]
  public function protocol40() {
    $this->assertEquals(
      new Bytes("UAXNPP\\O"), 
      new Bytes(MySqlPassword::$PROTOCOL_40->scramble('hello', '12345678'))
    );
  }

  #[Test]
  public function protocol41() {
    $this->assertEquals(
      new Bytes("}PQn\016s\013\013\033\022\373\252\033\240\207o=\262\304\335"), 
      new Bytes(MySqlPassword::$PROTOCOL_41->scramble('hello', '12345678901234567890'))
    );
  }
}