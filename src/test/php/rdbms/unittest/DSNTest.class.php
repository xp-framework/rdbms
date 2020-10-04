<?php namespace rdbms\unittest;

use lang\FormatException;
use rdbms\DSN;
use unittest\{Expect, Test};

/**
 * Tests the DSN class
 *
 * @see  xp://rdbms.DSN
 */
class DSNTest extends \unittest\TestCase {

  #[Test]
  public function stringRepresentationWithPassword() {
    $this->assertEquals(
      'rdbms.DSN@(sybase://sa:********@localhost:1999/CAFFEINE?autoconnect=1)',
      (new DSN('sybase://sa:password@localhost:1999/CAFFEINE?autoconnect=1'))->toString()
    );
  }
  
  #[Test]
  public function stringRepresentationWithoutPassword() {
    $this->assertEquals(
      'rdbms.DSN@(mysql://root@localhost/)',
      (new DSN('mysql://root@localhost/'))->toString()
    );
  }

  #[Test]
  public function asStringRemovesPassword() {
    $this->assertEquals(
      'mysql://user:********@localhost/',
      (new DSN('mysql://user:foobar@localhost/'))->asString()
    );
  }

  #[Test]
  public function asStringKeepsPasswordIfRequested() {
    $this->assertEquals(
      'mysql://user:foobar@localhost/',
      (new DSN('mysql://user:foobar@localhost/'))->asString(true)
    );
  }

  #[Test]
  public function asStringSkipsUserEvenWithRaw() {
    $this->assertEquals(
      'mysql://localhost/',
      (new DSN('mysql://localhost/'))->asString(true)
    );
  }

  #[Test]
  public function driver() {
    $this->assertEquals(
      'sybase', 
      (new DSN('sybase://TEST/'))->getDriver()
    );
  }

  #[Test, Expect(FormatException::class)]
  public function noDriver() {
    new DSN('');
  }

  #[Test]
  public function host() {
    $this->assertEquals(
      'TEST', 
      (new DSN('sybase://TEST/'))->getHost()
    );
  }
  
  #[Test]
  public function port() {
    $this->assertEquals(
      1999, 
      (new DSN('sybase://TEST:1999/'))->getPort()
    );
  }

  #[Test]
  public function portDefault() {
    $this->assertEquals(
      1999, 
      (new DSN('sybase://TEST:1999/'))->getPort(5000)
    );
  }

  #[Test]
  public function noPort() {
    $this->assertNull((new DSN('sybase://TEST/'))->getPort());
  }

  #[Test]
  public function noPortDefault() {
    $this->assertEquals(
      1999, 
      (new DSN('sybase://TEST/'))->getPort(1999)
    );
  }

  #[Test]
  public function database() {
    $this->assertEquals(
      'CAFFEINE', 
      (new DSN('sybase://TEST/CAFFEINE'))->getDatabase()
    );
  }

  #[Test]
  public function databaseDefault() {
    $this->assertEquals(
      'CAFFEINE', 
      (new DSN('sybase://TEST/CAFFEINE'))->getDatabase('master')
    );
  }

  #[Test]
  public function noDatabase() {
    $this->assertNull((new DSN('mysql://root@localhost'))->getDatabase());
  }

  #[Test]
  public function noDatabaseDefault() {
    $this->assertEquals(
      'master', 
      (new DSN('mysql://root@localhost'))->getDatabase('master')
    );
  }

  #[Test]
  public function slashDatabase() {
    $this->assertNull((new DSN('mysql://root@localhost/'))->getDatabase());
  }

  #[Test]
  public function slashDatabaseDefault() {
    $this->assertEquals(
      'master', 
      (new DSN('mysql://root@localhost/'))->getDatabase('master')
    );
  }

  #[Test]
  public function fileDatabase() {
    $this->assertEquals(
      '/usr/local/fb/jobs.fdb', 
      (new DSN('ibase://localhost//usr/local/fb/jobs.fdb'))->getDatabase()
    );
  }

  #[Test]
  public function user() {
    $this->assertEquals(
      'sa', 
      (new DSN('sybase://sa@TEST'))->getUser()
    );
  }

  #[Test]
  public function userDefault() {
    $this->assertEquals(
      'sa', 
      (new DSN('sybase://sa@TEST'))->getUser('reader')
    );
  }

  #[Test]
  public function noUser() {
    $this->assertNull((new DSN('sybase://TEST'))->getUser());
  }

  #[Test]
  public function noUserDefault() {
    $this->assertEquals(
      'reader', 
      (new DSN('sybase://TEST'))->getUser('reader')
    );
  }

  #[Test]
  public function password() {
    $this->assertEquals(
      'password', 
      (new DSN('sybase://sa:password@TEST'))->getPassword()
    );
  }

  #[Test]
  public function passwordDefault() {
    $this->assertEquals(
      'password', 
      (new DSN('sybase://sa:password@TEST'))->getPassword('secret')
    );
  }

  #[Test]
  public function noPassword() {
    $this->assertNull((new DSN('sybase://sa@TEST'))->getPassword());
  }

  #[Test]
  public function noPasswordDefault() {
    $this->assertEquals(
      'secret', 
      (new DSN('sybase://sa@TEST'))->getPassword('secret')
    );
  }
  
  #[Test]
  public function stringPropertyValue() {
    $this->assertEquals(
      'Europe/Berlin', 
      (new DSN('sybase://sa@TEST?tz=Europe/Berlin'))->getProperty('tz')
    );
  }

  #[Test]
  public function twoDsnsCreatedFromSameStringAreEqual() {
    $string= 'scheme://user:password@host/DATABASE?&autoconnect=1';
    $this->assertEquals(new DSN($string), new DSN($string));
  }

  #[Test]
  public function twoDsnsWithDifferingAutoconnectNotEqual() {
    $this->assertNotEquals(
      new DSN('scheme://user:password@host/DATABASE?autoconnect=0'), 
      new DSN('scheme://user:password@host/DATABASE?autoconnect=1')
    );
  }

  #[Test]
  public function twoDsnsWithDifferingParamsNotEqual() {
    $this->assertNotEquals(
      new DSN('scheme://user:password@host/DATABASE'), 
      new DSN('scheme://user:password@host/DATABASE?tz=Europe/Berlin')
    );
  }

  #[Test]
  public function twoDsnsWithDifferingFlagParamsNotEqual() {
    $this->assertNotEquals(
      new DSN('scheme://user:password@host/DATABASE'), 
      new DSN('scheme://user:password@host/DATABASE?autoconnect=1')
    );
  }

  #[Test]
  public function twoDsnsWithDifferentlyOrderedParamsAreEqual() {
    $this->assertEquals(
      new DSN('scheme://host/DATABASE?autoconnect=1&tz=Europe/Berlin'), 
      new DSN('scheme://host/DATABASE?tz=Europe/Berlin&autoconnect=1')
    );
  }

  #[Test]
  public function cloning() {
    $dsn= new DSN('mysql://root:password@localhost/');
    $clone= clone $dsn;
    $clone->url->setPassword(null);
    $this->assertEquals('password', $dsn->getPassword());
  }

  #[Test]
  public function withoutPassword() {
    $dsn= new DSN('mysql://root:password@localhost/');
    $clean= $dsn->withoutPassword();
    $this->assertNull($clean->getPassword());
  }
}