<?php namespace rdbms\unittest;

use lang\FormatException;
use rdbms\DSN;
use test\Assert;
use test\{Expect, Test};

/**
 * Tests the DSN class
 *
 * @see  xp://rdbms.DSN
 */
class DSNTest {

  #[Test]
  public function stringRepresentationWithPassword() {
    Assert::equals(
      'rdbms.DSN@(sybase://sa:********@localhost:1999/CAFFEINE?autoconnect=1)',
      (new DSN('sybase://sa:password@localhost:1999/CAFFEINE?autoconnect=1'))->toString()
    );
  }
  
  #[Test]
  public function stringRepresentationWithoutPassword() {
    Assert::equals(
      'rdbms.DSN@(mysql://root@localhost/)',
      (new DSN('mysql://root@localhost/'))->toString()
    );
  }

  #[Test]
  public function asStringRemovesPassword() {
    Assert::equals(
      'mysql://user:********@localhost/',
      (new DSN('mysql://user:foobar@localhost/'))->asString()
    );
  }

  #[Test]
  public function asStringKeepsPasswordIfRequested() {
    Assert::equals(
      'mysql://user:foobar@localhost/',
      (new DSN('mysql://user:foobar@localhost/'))->asString(true)
    );
  }

  #[Test]
  public function asStringSkipsUserEvenWithRaw() {
    Assert::equals(
      'mysql://localhost/',
      (new DSN('mysql://localhost/'))->asString(true)
    );
  }

  #[Test]
  public function driver() {
    Assert::equals(
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
    Assert::equals(
      'TEST', 
      (new DSN('sybase://TEST/'))->getHost()
    );
  }
  
  #[Test]
  public function port() {
    Assert::equals(
      1999, 
      (new DSN('sybase://TEST:1999/'))->getPort()
    );
  }

  #[Test]
  public function portDefault() {
    Assert::equals(
      1999, 
      (new DSN('sybase://TEST:1999/'))->getPort(5000)
    );
  }

  #[Test]
  public function noPort() {
    Assert::null((new DSN('sybase://TEST/'))->getPort());
  }

  #[Test]
  public function noPortDefault() {
    Assert::equals(
      1999, 
      (new DSN('sybase://TEST/'))->getPort(1999)
    );
  }

  #[Test]
  public function database() {
    Assert::equals(
      'CAFFEINE', 
      (new DSN('sybase://TEST/CAFFEINE'))->getDatabase()
    );
  }

  #[Test]
  public function databaseDefault() {
    Assert::equals(
      'CAFFEINE', 
      (new DSN('sybase://TEST/CAFFEINE'))->getDatabase('master')
    );
  }

  #[Test]
  public function noDatabase() {
    Assert::null((new DSN('mysql://root@localhost'))->getDatabase());
  }

  #[Test]
  public function noDatabaseDefault() {
    Assert::equals(
      'master', 
      (new DSN('mysql://root@localhost'))->getDatabase('master')
    );
  }

  #[Test]
  public function slashDatabase() {
    Assert::null((new DSN('mysql://root@localhost/'))->getDatabase());
  }

  #[Test]
  public function slashDatabaseDefault() {
    Assert::equals(
      'master', 
      (new DSN('mysql://root@localhost/'))->getDatabase('master')
    );
  }

  #[Test]
  public function fileDatabase() {
    Assert::equals(
      '/usr/local/fb/jobs.fdb', 
      (new DSN('ibase://localhost//usr/local/fb/jobs.fdb'))->getDatabase()
    );
  }

  #[Test]
  public function user() {
    Assert::equals(
      'sa', 
      (new DSN('sybase://sa@TEST'))->getUser()
    );
  }

  #[Test]
  public function userDefault() {
    Assert::equals(
      'sa', 
      (new DSN('sybase://sa@TEST'))->getUser('reader')
    );
  }

  #[Test]
  public function noUser() {
    Assert::null((new DSN('sybase://TEST'))->getUser());
  }

  #[Test]
  public function noUserDefault() {
    Assert::equals(
      'reader', 
      (new DSN('sybase://TEST'))->getUser('reader')
    );
  }

  #[Test]
  public function password() {
    Assert::equals(
      'password', 
      (new DSN('sybase://sa:password@TEST'))->getPassword()
    );
  }

  #[Test]
  public function passwordDefault() {
    Assert::equals(
      'password', 
      (new DSN('sybase://sa:password@TEST'))->getPassword('secret')
    );
  }

  #[Test]
  public function noPassword() {
    Assert::null((new DSN('sybase://sa@TEST'))->getPassword());
  }

  #[Test]
  public function noPasswordDefault() {
    Assert::equals(
      'secret', 
      (new DSN('sybase://sa@TEST'))->getPassword('secret')
    );
  }
  
  #[Test]
  public function stringPropertyValue() {
    Assert::equals(
      'Europe/Berlin', 
      (new DSN('sybase://sa@TEST?tz=Europe/Berlin'))->getProperty('tz')
    );
  }

  #[Test]
  public function twoDsnsCreatedFromSameStringAreEqual() {
    $string= 'scheme://user:password@host/DATABASE?&autoconnect=1';
    Assert::equals(new DSN($string), new DSN($string));
  }

  #[Test]
  public function twoDsnsWithDifferingAutoconnectNotEqual() {
    Assert::notEquals(
      new DSN('scheme://user:password@host/DATABASE?autoconnect=0'), 
      new DSN('scheme://user:password@host/DATABASE?autoconnect=1')
    );
  }

  #[Test]
  public function twoDsnsWithDifferingParamsNotEqual() {
    Assert::notEquals(
      new DSN('scheme://user:password@host/DATABASE'), 
      new DSN('scheme://user:password@host/DATABASE?tz=Europe/Berlin')
    );
  }

  #[Test]
  public function twoDsnsWithDifferingFlagParamsNotEqual() {
    Assert::notEquals(
      new DSN('scheme://user:password@host/DATABASE'), 
      new DSN('scheme://user:password@host/DATABASE?autoconnect=1')
    );
  }

  #[Test]
  public function twoDsnsWithDifferentlyOrderedParamsAreEqual() {
    Assert::equals(
      new DSN('scheme://host/DATABASE?autoconnect=1&tz=Europe/Berlin'), 
      new DSN('scheme://host/DATABASE?tz=Europe/Berlin&autoconnect=1')
    );
  }

  #[Test]
  public function cloning() {
    $dsn= new DSN('mysql://root:password@localhost/');
    $clone= clone $dsn;
    $clone->url->setPassword(null);
    Assert::equals('password', $dsn->getPassword());
  }

  #[Test]
  public function withoutPassword() {
    $dsn= new DSN('mysql://root:password@localhost/');
    $clean= $dsn->withoutPassword();
    Assert::null($clean->getPassword());
  }
}