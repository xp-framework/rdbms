<?php namespace rdbms\unittest;

use lang\{FormatException, IllegalArgumentException};
use rdbms\{DriverManager, DriverNotSupportedException};
use unittest\{Expect, Test};

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

  #[Test, Expect(DriverNotSupportedException::class)]
  public function unsupportedDriver() {
    DriverManager::getConnection('unsupported://localhost');
  }

  #[Test, Expect(FormatException::class)]
  public function nullConnection() {
    DriverManager::getConnection(null);
  }

  #[Test, Expect(FormatException::class)]
  public function emptyConnection() {
    DriverManager::getConnection('');
  }

  #[Test, Expect(FormatException::class)]
  public function malformedConnection() {
    DriverManager::getConnection('not.a.dsn');
  }

  #[Test]
  public function mysqlxProvidedByDefaultDrivers() {
    $this->assertInstanceOf(
      'rdbms.mysqlx.MySqlxConnection', 
      DriverManager::getConnection('mysql+x://localhost')
    );
  }

  #[Test, Expect(DriverNotSupportedException::class)]
  public function unsupportedDriverInMySQLDriverFamily() {
    DriverManager::getConnection('mysql+unsupported://localhost');
  }

  #[Test]
  public function mysqlAlwaysSupported() {
    $this->assertInstanceOf(
      'rdbms.DBConnection', 
      DriverManager::getConnection('mysql://localhost')
    );
  }

  #[Test]
  public function registerConnection() {
    $this->register('mock', \lang\XPClass::forName('rdbms.unittest.mock.MockConnection'));
    $this->assertInstanceOf(
      'rdbms.unittest.mock.MockConnection',
      DriverManager::getConnection('mock://localhost')
    );
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function registerNonDbConnection() {
    $this->register('fail', typeof($this));
  }

  #[Test]
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