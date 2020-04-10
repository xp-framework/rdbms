<?php namespace rdbms\unittest\tds;

use io\File;
use rdbms\DSN;
use rdbms\tds\FreeTdsLookup;

/**
 * TestCase
 *
 * @see   xp://rdbms.tds.FreeTdsLookup#locateConf
 */
class FreeTdsConfigLocationTest extends \unittest\TestCase {

  #[@test]
  public function noAlternativesFound() {
    $fixture= newinstance(FreeTdsLookup::class, [], [
      'parse' => function() { throw new IllegalStateException('Should never be called!'); },
      'locateConf' => function() { return null; }
    ]);

    $dsn= new DSN('sybase://test');
    $fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://test'), $dsn);
  }

  #[@test]
  public function fileReturned() {
    $fixture= newinstance(FreeTdsLookup::class, [],  [
      'parse' => function() { return ['test' => ['host' => $this->conf->getFilename(), 'port' => 1999]]; },
      'locateConf' => function() { return new File('it.worked'); }
    ]);

    $dsn= new DSN('sybase://test');
    $fixture->lookup($dsn);
    $this->assertEquals(new DSN('sybase://it.worked:1999'), $dsn);
  }
}