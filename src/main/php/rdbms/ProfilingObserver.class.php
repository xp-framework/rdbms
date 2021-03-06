<?php namespace rdbms;

use lang\IllegalArgumentException;
use util\Observer;
use util\log\Traceable;
use util\profiling\Timer;

/**
 * Profiling database observer
 *
 * @test  xp://rdbms.unittest.ProfilingObserverTest
 */
class ProfilingObserver implements Observer {
  const COUNT= 0x01;
  const TIMES= 0x02;

  private $cat    = null;
  private $timer  = null;
  private $lastq  = null;
  private $dsn    = null;
  private $timing = [];
  
  /**
   * Creates a new log observer with a given log category.
   *
   * @param  util.log.LogCategory $cat
   */
  public function __construct($cat) {
    $this->cat= $cat;
  }

  /**
   * Returns the type of SQL query, one of update, insert, select,
   * delete, set, show, or unknown if the type cannot be determined.
   *
   * @param  string $sql The raw SQL
   * @return string
   */
  public function typeOf($sql) {
    $sql= ltrim($sql);
    $verb= strtolower(substr($sql, 0, strpos($sql, ' ')));

    return in_array($verb, ['update', 'insert', 'select', 'delete', 'set', 'show']) ? $verb : 'unknown';
  }

  /**
   * Update method
   *
   * @param   util.Observable obs
   * @param   var arg default NULL
   */
  public function update($obs, $arg= null) {
    if (!$obs instanceof DBConnection) {
      throw new IllegalArgumentException('Argument 1 must be instanceof "rdbms.DBConnection", "'.typeof($obs)->getName().'" given.');
    }
    if (!$arg instanceof DBEvent) return;

    // Store reference for later reuse
    if (null === $this->dsn) $this->dsn= $obs->getDSN()->withoutPassword();

    $method= $arg->getName();
    switch ($method) {
      case 'query':
      case 'open':
        $this->lastq= $this->typeOf($arg->getArgument());
        // Fallthrough intentional

      case 'connect': {
        if ('connect' == $method) $this->lastq= $method;
        $this->timer= new Timer();
        $this->timer->start();

        // Count some well-known SQL keywords
        $this->countFor($this->lastq);

        break;
      }

      case 'connected':
      case 'queryend': {

        // Protect against illegal order of events (should not occur)
        if (!$this->timer) return;
        $this->timer->stop();

        $this->addElapsedTimeTo($method, $this->timer->elapsedTime());
        if ($this->lastq) {
          $this->addElapsedTimeTo($this->lastq, $this->timer->elapsedTime());
          $this->lastq= null;
        }

        $this->timer= null;
        break;
      }
    }
  }

  /**
   * Emit recorded timings to LogCategory
   */
  public function emitTimings() {
    if ($this->cat && $this->dsn) {
      $this->cat->info(__CLASS__, 'for', sprintf('%s://%s@%s/%s',
        $this->dsn->getDriver(),
        $this->dsn->getUser(),
        $this->dsn->getHost(),
        $this->dsn->getDatabase()
        ), $this->getTimingAsString()
      );
    }
  }

  /**
   * Get gathered timing values as string
   *
   * @return string
   */
  public function getTimingAsString() {
    $s= '';

    foreach ($this->timing as $type => $details) {
      $s.= sprintf("%s: [%0.3fs%s], ",
        $type,
        $details[self::TIMES],
        isset($details[self::COUNT]) ? sprintf(' in %d queries', $details[self::COUNT]) : ''
      );
    }

    return substr($s, 0, -2);
  }

  /**
   * Count statements per type
   *
   * @param  string $type
   */
  protected function countFor($type) {
    if (!isset($this->timing[$type][self::COUNT])) $this->timing[$type][self::COUNT]= 0;
    $this->timing[$type][self::COUNT]++;
  }

  /**
   * Add timing values for a given type
   *
   * @param string $type
   * @param double $elapsed
   */
  protected function addElapsedTimeTo($type, $elapsed) {
    if (!isset($this->timing[$type][self::TIMES])) $this->timing[$type][self::TIMES]= 0;
    $this->timing[$type][self::TIMES]+= $elapsed;
  }

  /**
   * Returns number of statemens per type counted via `countFor()`
   *
   * @param  string $type
   * @return int
   */
  public function numberOfTimes($type) {
    if (!isset($this->timing[$type][self::COUNT])) return 0;
    return $this->timing[$type][self::COUNT];
  }

  /**
   * Returns sum of timings per type counted via `addElapsedTimeTo()`
   *
   * @param  string $type
   * @return double
   */
  public function elapsedTimeOfAll($type) {
    if (!isset($this->timing[$type][self::TIMES])) return 0.0;
    return $this->timing[$type][self::TIMES];
  }

  /** 
   * Destructor; invoke emitTimings() if observer had recorded any activity.
   */
  public function __destruct() {

    // Check if we're holding a reference to a LogCategory - then update() had been
    // called once, and we probably have something to say
    if ($this->cat) {
      $this->emitTimings();
    }
  }
}