<?php namespace rdbms;

use util\Objects;

/**
 * Manages database drivers
 *
 * DSNs
 * ====
 * The DriverManager class expects a unified connection string (we call 
 * it DSN) specifying the following: 
 *
 * - The driver (here: `sybase`). This corresponds either to a built-in
 *   driver class or one you have previously registered to it.
 * - An optional username and password (here: `user` and `pass`).
 * - The hostname of the rdbms (here: `server`). Hostname is not completely
 *   correct: in SQLite, for example, this specifies the name of the data
 *   file; in Sybase, it corresponds to an entry in the interfaces file.
 * - The database name (here: `NICOTINE`).
 *   May be ommitted - for instance, Sybase offers a per-user default 
 *   database setting which automatically selects the specified database 
 *   after log in.
 * - Optional parameters (here: none).

 * Parameters in DSN are used in a key-value syntax as known from HTTP 
 * urls, e.g. `mysql://user:pass@server?autoconnect=1`.
 *
 * These parameters are recognized: 
 *
 * - *autoconnect=value* - A call to rdbms.DBConnection#connect may be 
 *   ommitted. Just go ahead and when calling the first method which 
 *   needs to connect (and log in), a connection will be established.
 *   Value is an integer of either 1 (on) or 0 (off). Default is 1 (on). 
 * - *tz=Europe/Berlin* - Database server timezones, using Olson notation.
 *   Default is to use current timezone, and not convert dates.
 * - *timeout=value* - Sets a connection timeout. Value is an integer 
 *   specifying the number of seconds to wait before cancelling a connect/ 
 *   log on procedure. Default may vary between different RDBMS. 
 *
 * Usage
 * =====
 * ```php
 * use rdbms\DriverManager;
 *   
 * $conn= DriverManager::getConnection('sybase://user:pass@server');
 * $conn->connect();
 *   
 * Console::writeLine($conn->query('select @@version as version')->next('version'));
 * ```
 *
 * @test     xp://net.xp_framework.unittest.rdbms.DriverManagerTest
 */
class DriverManager {
  protected static $instance= null;
  public $drivers= [];
  protected $lookup= [];
  protected $provider= null;

  static function __static() {
    self::$instance= new self();
    self::$instance->provider= new DefaultDrivers(null);
  }
  
  /**
   * Constructor.
   *
   */
  protected function __construct() {
  }
    
  /**
   * Gets an instance
   *
   * @return  rdbms.DriverManager
   */
  public static function getInstance() {
    return self::$instance;
  }

  /**
   * Register a driver
   *
   * Usage:
   * <code>
   *   DriverManager::register('mydb', XPClass::forName('my.db.Connection'));
   *   // [...]
   *   $conn= DriverManager::getConnection('mydb://...');
   * </code>
   *
   * @param   string name identifier
   * @param   lang.XPClass<rdbms.DBConnection> class
   * @throws  lang.IllegalArgumentException in case an incorrect class is given
   */
  public static function register($name, \lang\XPClass $class) {
    if (!$class->isSubclassOf('rdbms.DBConnection')) {
      throw new \lang\IllegalArgumentException(sprintf(
        'Given argument must be lang.XPClass<rdbms.DBConnection>, %s given',
        $class->toString()
      ));
    }
    self::$instance->drivers[$name]= $class;
    self::$instance->lookup= [];
  }
  
  /**
   * Remove a driver
   *
   * @param   string name
   */
  public static function remove($name) {
    unset(self::$instance->drivers[$name]);
    self::$instance->lookup= [];
  }
  
  /**
   * Get a connection by a DSN string
   *
   * @param   string|rdbms.DSN $dsn
   * @return  rdbms.DBConnection
   * @throws  rdbms.DriverNotSupportedException
   */
  public static function getConnection($dsn) {
    $dsn= $dsn instanceof DSN ? $dsn : new DSN((string)$dsn);
    $driver= $dsn->getDriver();

    // Lookup driver by identifier, if no direct match is found, choose from 
    // the drivers with the same driver identifier. If no implementation can
    // be found that way, ask available rdbms.DriverImplementationsProviders 
    if (!isset(self::$instance->lookup[$driver])) {
      if (isset(self::$instance->drivers[$driver])) {
        self::$instance->lookup[$driver]= self::$instance->drivers[$driver];
      } else {
        $provider= self::$instance->provider;

        // Normalize driver, then query providers for available implementations
        if (false === ($p= strpos($driver, '+'))) {
          $family= $driver;
          $search= $driver.'+';
        } else {
          $family= substr($driver, 0, $p);
          $search= $driver;
        }
        foreach ($provider->implementationsFor($family) as $impl) {
          \lang\XPClass::forName($impl);
        }

        // Not every implementation may be registered (e.g., due to a missing 
        // prerequisite), so now search the registered implementations for a 
        // suitable driver.
        $l= strlen($search);
        do {
          foreach (self::$instance->drivers as $name => $class) {
            if (0 !== strncmp($name, $search, $l)) continue;
            self::$instance->lookup[$driver]= $class;
            break 2;
          }
          throw new DriverNotSupportedException(sprintf(
            'No driver registered for "%s" or provided by any of %s',
            $driver,
            Objects::stringOf($provider)
          ));
        } while (0);
      }
    }
    
    return self::$instance->lookup[$driver]->newInstance($dsn);
  }
}