<?php namespace rdbms\unittest\sqlite3;

use lang\IllegalStateException;
use rdbms\sqlite3\{SQLite3Connection, SQLite3ResultSet};
use rdbms\{DSN, SQLStateException, SQLStatementFailedException};
use unittest\actions\ExtensionAvailable;
use unittest\{Assert, After, Expect, Test};

#[Action(eval: 'new ExtensionAvailable("sqlite3")')]
class SQLite3ConnectionTest {
  private $close= [];

  #[After]
  public function disconnect() {
    foreach ($this->close as $conn) {
      $conn->close();
    }
  }

  /** @return rdbms.unittest.mock.MockConnection */
  private function connection() {
    $conn= new SQLite3Connection(new DSN('sqlite+3:///:memory:?autoconnect=1'));
    $this->close[]= $conn;
    return $conn;
  }

  #[Test]
  public function close() {
    Assert::false($this->connection()->close());
  }

  #[Test]
  public function connect_then_close_both_return_true() {
    $conn= $this->connection();
    Assert::true($conn->connect());
    Assert::true($conn->close());
  }

  #[Test]
  public function second_close_call_returns_false() {
    $conn= $this->connection();
    Assert::true($conn->connect());
    Assert::true($conn->close());
    Assert::false($conn->close());
  }

  #[Test, Expect(SQLStatementFailedException::class)]
  public function selectdb() {
    $this->connection()->selectdb('foo');
  }

  #[Test]
  public function query() {
    $conn= $this->connection();
    $conn->connect();
    $result= $conn->query('select 1 as one');
    
    Assert::instance(SQLite3ResultSet::class, $result);
    Assert::equals(['one' => 1], $result->next());
  }

  #[Test]
  public function query_returns_success_on_empty_resultset() {
    $conn= $this->connection();
    $conn->connect();
    Assert::true($conn->query('pragma user_version = 1')->isSuccess());
  }

  #[Test, Expect(SQLStatementFailedException::class)]
  public function query_throws_exception_for_broken_statement() {
    $conn= $this->connection();
    $conn->connect();
    $conn->query('select something with wrong syntax');
  }

  #[Test]
  public function query_returns_result_for_empty_resultset() {
    $conn= $this->connection();
    $conn->connect();
    $result= $conn->query('select 1 where 1 = 0');

    Assert::instance(SQLite3ResultSet::class, $result);
    Assert::null($result->next());
  }

  #[Test]
  public function create_table_and_fill() {
    $conn= $this->connection();
    $conn->query('create temp table testthewest (
      col1 integer primary key asc,
      str2 text,
      col3 real,
      col4 numeric
    )');

    $q= $conn->insert('into testthewest (str2, col3, col4) values (%s, %f, %f)',
      "Hello World",
      1.5,
      12345.67
    );
    Assert::equals(1, $q); // 1 Row inserted
    Assert::equals(1, $conn->identity());
    Assert::equals(
      [[
        'col1' => 1,
        'str2' => 'Hello World',
        'col3' => 1.5,
        'col4' => 12345.67
      ]],
      $conn->select('* from testthewest')
    );
  }

  #[Test]
  public function unbuffered_queries_simulated() {
    $conn= $this->connection();
    $conn->connect();
    Assert::equals([1 => 1], $conn->query('select 1', false)->next());
  }

  #[Test, Expect(SQLStateException::class)]
  public function identity_throws_exception_when_not_connected() {
    $conn= $this->connection();
    $conn->identity();
  }
}