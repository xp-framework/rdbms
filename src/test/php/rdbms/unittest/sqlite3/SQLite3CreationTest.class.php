<?php namespace rdbms\unittest\sqlite3;

use rdbms\SQLConnectException;
use rdbms\sqlite3\SQLite3Connection;
use unittest\actions\{ExtensionAvailable, VerifyThat};

/**
 * Testcase for rdbms.sqlite3.SQLite3Connection
 *
 * @see   xp://rdbms.sqlite3.SQLite3Connection
 * @see   https://github.com/xp-framework/xp-framework/issues/107
 * @see   https://github.com/xp-framework/xp-framework/issues/111
 * @see   https://bugs.php.net/bug.php?id=55154
 */
#[@action([
#  new ExtensionAvailable('sqlite3'),
#  new VerifyThat(function() { return !defined('HHVM_VERSION'); })
#])]
class SQLite3CreationTest extends \unittest\TestCase {

  #[@test]
  public function connect_dot() {
    $conn= new SQLite3Connection(new \rdbms\DSN('sqlite+3://./:memory:'));
    $conn->connect();
  }

  #[@test, @expect(SQLConnectException::class)]
  public function connect_does_not_support_remote_hosts() {
    $conn= new SQLite3Connection(new \rdbms\DSN('sqlite+3://some.host/:memory:'));
    $conn->connect();
  }

  #[@test, @expect(SQLConnectException::class)]
  public function connect_fails_for_invalid_filenames() {
    $conn= new SQLite3Connection(new \rdbms\DSN('sqlite+3://./'));
    $conn->connect();
  }

  #[@test, @expect(SQLConnectException::class)]
  public function connect_fails_for_filenames_with_urlencoded_nul() {
    $conn= new SQLite3Connection(new \rdbms\DSN('sqlite+3://./%00'));
    $conn->connect();
  }

  #[@test, @expect(SQLConnectException::class)]
  public function connect_does_not_support_streams() {
    $conn= new SQLite3Connection(new \rdbms\DSN('sqlite+3://./res://database.db'));
    $conn->connect();
  }
}