<?php namespace rdbms\unittest\tds;

use rdbms\DSN;
use rdbms\tds\SqlIniLookup;
use unittest\Assert;
use unittest\Test;

/**
 * TestCase
 *
 * @see   xp://rdbms.tds.SqlIniLookup
 */
class SqlIniLookupTest {
  protected $fixture= null;

  /**
   * Sets up test case
   */
  #[Before]
  public function setUp() {
    $this->fixture= new SqlIniLookup(typeof($this)->getPackage()->getResourceAsStream('sql.ini'));
  }
  
  #[Test]
  public function lookup() {
    $dsn= new DSN('sybase://CARLA');
    $this->fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://carla.example.com:5000'), $dsn);
  }

  #[Test]
  public function lookupCaseInsensitive() {
    $dsn= new DSN('sybase://carla');
    $this->fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://carla.example.com:5000'), $dsn);
  }

  #[Test]
  public function lookupNonExistantHost() {
    $dsn= new DSN('sybase://nonexistant');
    $this->fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://nonexistant'), $dsn);
  }

  #[Test]
  public function lookupExistingHostWithoutQueryKey() {
    $dsn= new DSN('sybase://banane');
    $this->fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://banane'), $dsn);
  }

  #[Test]
  public function lookupIpv4() {
    $dsn= new DSN('sybase://wurst4');
    $this->fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://192.0.43.10:1998'), $dsn);
  }


  #[Test]
  public function lookupIpv6() {
    $dsn= new DSN('sybase://wurst6');
    $this->fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://[2001:500:88:200::10]:1998'), $dsn);
  }
}