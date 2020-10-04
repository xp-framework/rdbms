<?php namespace rdbms\unittest\tds;

use rdbms\DSN;
use rdbms\tds\InterfacesLookup;
use unittest\Test;

/**
 * TestCase
 *
 * @see   xp://rdbms.tds.InterfacesLookup
 */
class InterfacesLookupTest extends \unittest\TestCase {
  protected $fixture= null;

  /**
   * Sets up test case
   */
  public function setUp() {
    $this->fixture= new InterfacesLookup(typeof($this)->getPackage()->getResourceAsStream('interfaces'));
  }
  
  #[Test]
  public function lookup() {
    $dsn= new DSN('sybase://CARLA');
    $this->fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://carla.example.com:5000'), $dsn);
  }

  #[Test]
  public function lookupCaseInsensitive() {
    $dsn= new DSN('sybase://carla');
    $this->fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://carla.example.com:5000'), $dsn);
  }

  #[Test]
  public function lookupNonExistantHost() {
    $dsn= new DSN('sybase://nonexistant');
    $this->fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://nonexistant'), $dsn);
  }

  #[Test]
  public function lookupExistingHostWithoutQueryKey() {
    $dsn= new DSN('sybase://banane');
    $this->fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://banane'), $dsn);
  }

  #[Test]
  public function lookupIpv4() {
    $dsn= new DSN('sybase://wurst4');
    $this->fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://192.0.43.10:1998'), $dsn);
  }


  #[Test]
  public function lookupKeyIndentedWithTabs() {
    $dsn= new DSN('sybase://tabs');
    $this->fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://tabs.example.com:1999'), $dsn);
  }
}