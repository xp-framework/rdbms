<?php namespace rdbms\unittest;

use rdbms\DriverNotSupportedException;
use lang\FormatException;
use lang\IllegalArgumentException;
use rdbms\DriverManager;

/**
 * TestCase
 *
 * @see  xp://rdbms.DriverManager
 */
class DriverManagerTest extends \unittest\TestCase {
  protected $registered= [];

  /**
   * Registers driver and tracks registration.
   *
   * @param   string name
   * @param   lang.XPClass class
   */
  protected function register($name, $class) {
    DriverManager::register($name, $class);
    $this->registered[]= $name;
  }
  
  /**
   * Tears down test case - removes all drivers registered via register().
   */
  public function tearDown() {
    foreach ($this->registered as $name) {
      DriverManager::remove($name);
    }
  }

  #[@test, @expect(DriverNotSupportedException::class)]
  public function unsupportedDriver() {
    DriverManager::getConnection('unsupported://localhost');
  }

  #[@test, @expect(FormatException::class)]
  public function nullConnection() {
    DriverManager::getConnection(null);
  }

  #[@test, @expect(FormatException::class)]
  public function emptyConnection() {
    DriverManager::getConnection('');
  }

  #[@test, @expect(FormatException::class)]
  public function malformedConnection() {
    DriverManager::getConnection('not.a.dsn');
  }

  #[@test]
  public function mysqlxProvidedByDefaultDrivers() {
    $this->assertInstanceOf(
      'rdbms.mysqlx.MySqlxConnection', 
      DriverManager::getConnection('mysql+x://localhost')
    );
  }

  #[@test, @expect(DriverNotSupportedException::class)]
  public function unsupportedDriverInMySQLDriverFamily() {
    DriverManager::getConnection('mysql+unsupported://localhost');
  }

  #[@test]
  public function mysqlAlwaysSupported() {
    $this->assertInstanceOf(
      'rdbms.DBConnection', 
      DriverManager::getConnection('mysql://localhost')
    );
  }

  #[@test]
  public function registerConnection() {
    $this->register('mock', \lang\XPClass::forName('rdbms.unittest.mock.MockConnection'));
    $this->assertInstanceOf(
      'rdbms.unittest.mock.MockConnection',
      DriverManager::getConnection('mock://localhost')
    );
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function registerNonDbConnection() {
    $this->register('fail', $this->getClass());
  }

  #[@test]
  public function searchImplementation() {

    // Should not be found
    $this->register('tests', \lang\XPClass::forName('rdbms.unittest.mock.MockConnection'));

    // Should choose the "a" implementation
    $this->register('test+a', \lang\ClassLoader::defineClass(
      'rdbms.unittest.mock.AMockConnection', 
      'rdbms.unittest.mock.MockConnection', 
      [], 
      '{}'
    ));
    $this->register('test+b', \lang\ClassLoader::defineClass(
      'rdbms.unittest.mock.BMockConnection', 
      'rdbms.unittest.mock.MockConnection', 
      [], 
      '{}'
    ));

    $this->assertInstanceOf(
      'rdbms.unittest.mock.AMockConnection', 
      DriverManager::getConnection('test://localhost')
    );
  }
}
