<?php namespace rdbms\unittest;

use lang\IllegalArgumentException;
use rdbms\sqlite3\SQLite3Connection;
use rdbms\{DBEvent, DSN, ProfilingObserver};
use unittest\{Expect, Ignore, Test, TestCase, Values};
use util\log\{BufferedAppender, LogCategory};

/**
 * Testcase for the profiling observer class
 *
 * @see   xp://rdbms.ProfilingObserver
 */
class ProfilingObserverTest extends TestCase {
  private $cat;

  /** @return void */
  public function setUp() {
    $this->cat= new LogCategory('test');
  }

  /** @return rdbms.DBConnection */
  private function conn() {
    return new SQLite3Connection(new DSN('sqlite+3:///foo.sqlite'));
  }

  /**
   * Returns a profiling observer instance which has already "run" a query
   *
   * @return rdbms.ProfilingObserver
   */
  private function observerWithSelect() {
    $o= new ProfilingObserver($this->cat);
    $conn= $this->conn();
    $o->update($conn, new DBEvent('query', 'select * from world'));
    usleep(100000);
    $o->update($conn, new DBEvent('queryend', 5));

    return $o;
  }

  #[Test]
  public function create() {
    new ProfilingObserver($this->cat);
  }

  #[Test, Values(['select * from world', ' select * from world', '  select * from world', "\rselect * from world", "\r\nselect * from world", "\nselect * from world", "\tselect * from world", 'SELECT * from world', 'Select * from world'])]
  public function select_type($sql) {
    $this->assertEquals('select', (new ProfilingObserver($this->cat))->typeOf($sql));
  }

  public function update_type() {
    $this->assertEquals('update', (new ProfilingObserver($this->cat))->typeOf('update world set ...'));
  }

  public function insert_type() {
    $this->assertEquals('insert', (new ProfilingObserver($this->cat))->typeOf('insert into world ...'));
  }

  public function delete_type() {
    $this->assertEquals('delete', (new ProfilingObserver($this->cat))->typeOf('delete from world ...'));
  }

  public function set_type() {
    $this->assertEquals('set', (new ProfilingObserver($this->cat))->typeOf('set showplan on'));
  }

  public function show_type() {
    $this->assertEquals('show', (new ProfilingObserver($this->cat))->typeOf('show keys from ...'));
  }

  public function unknown_type() {
    $this->assertEquals('unknown', (new ProfilingObserver($this->cat))->typeOf('explain ...'));
  }

  #[Test]
  public function emitTiming_without_actually_having_any_timing_does_not_fatal() {
    (new ProfilingObserver($this->cat))->emitTimings();
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function update_with_anyarg() {
    $o= new ProfilingObserver($this->cat);
    $o->update(1);
  }

  #[Test]
  public function update_with_event() {
    $o= new ProfilingObserver($this->cat);
    $o->update($this->conn(), new DBEvent('hello', 'select * from world'));
  }

  #[Test]
  public function update_with_query_and_queryend_does_count_timing() {
    $o= $this->observerWithSelect();

    $this->assertEquals(1, $o->numberOfTimes('select'));
  }

  #[Test]
  public function update_with_query_and_queryend_does_time_aggregation() {
    $o= $this->observerWithSelect();

    $elapsed= $o->elapsedTimeOfAll('queryend');
    $this->assertFalse(0 == $elapsed, $elapsed.'!= 0');
    $this->assertTrue($elapsed >= 0.098, $elapsed.' >= 0.098');
  }

  #[Test]
  public function timing_as_string() {
    $o= $this->observerWithSelect();
    $this->assertTrue(0 < strlen($o->getTimingAsString()));
  }

  #[Test]
  public function destructor_emits_timing() {
    $o= $this->observerWithSelect();
    $appender= $this->cat->addAppender(new BufferedAppender());
    $o= null;

    $this->assertTrue(0 < strlen($appender->buffer));
  }

  #[Test]
  public function dbevent_in_illegal_order_is_ignored() {
    $o= new ProfilingObserver($this->cat);
    $conn= $this->conn();

    $o->update($conn, new DBEvent('queryend', 5));
    $this->assertEquals(0.0, $o->elapsedTimeOfAll('queryend'));
  }

  #[Test]
  public function connect_is_counted_as_verb() {
    $o= new ProfilingObserver($this->cat);

    $c1= $this->conn();
    $o->update($c1, new DBEvent('connect'));
    $o->update($c1, new DBEvent('connected'));

    $this->assertEquals(1, $o->numberOfTimes('connect'));
  }

  #[Test, Ignore('Expected behavior not finally decided')]
  public function observer_only_listens_to_one_dbconnection() {
    $o= new ProfilingObserver($this->cat);

    $c1= $this->conn();
    $o->update($c1, new DBEvent('connect'));
    $o->update($c1, new DBEvent('connected'));

    $c2= $this->conn();
    $o->update($c2, new DBEvent('connect'));
    $o->update($c2, new DBEvent('connected'));

    $this->assertEquals(1, $o->numberOfTimes('connect'));
  }

  #[Test]
  public function unknown_sql_token_is_classified_as_unknown() {
    $o= new ProfilingObserver($this->cat);

    $c1= $this->conn();
    $o->update($c1, new DBEvent('query', 'encrypt foo from bar'));;
    $o->update($c1, new DBEvent('queryend'));

    $this->assertEquals(1, $o->numberOfTimes('unknown'));
  }

  #[Test]
  public function update_sql_token_is_classified_as_unknown() {
    $o= new ProfilingObserver($this->cat);

    $c1= $this->conn();
    $o->update($c1, new DBEvent('query', 'update foo from bar'));;
    $o->update($c1, new DBEvent('queryend'));

    $this->assertEquals(1, $o->numberOfTimes('update'));
  }
}