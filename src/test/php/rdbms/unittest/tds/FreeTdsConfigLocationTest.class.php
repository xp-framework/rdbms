<?php namespace rdbms\unittest\tds;

use io\File;
use rdbms\DSN;
use rdbms\tds\FreeTdsLookup;
use test\Assert;
use test\Test;

/**
 * TestCase
 *
 * @see   xp://rdbms.tds.FreeTdsLookup#locateConf
 */
class FreeTdsConfigLocationTest {

  #[Test]
  public function noAlternativesFound() {
    $fixture= new class() extends FreeTdsLookup {
      public function parse() { throw new IllegalStateException('Should never be called!'); }
      public function locateConf() { return null; }
    };

    $dsn= new DSN('sybase://test');
    $fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://test'), $dsn);
  }

  #[Test]
  public function fileReturned() {
    $fixture= new class() extends FreeTdsLookup {
      public function parse() { return ['test' => ['host' => $this->conf->getFilename(), 'port' => 1999]]; }
      public function locateConf() { return new File('it.worked'); }
    };

    $dsn= new DSN('sybase://test');
    $fixture->lookup($dsn);
    Assert::equals(new DSN('sybase://it.worked:1999'), $dsn);
  }
}