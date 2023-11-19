<?php namespace rdbms\unittest;

use io\streams\MemoryInputStream;
use rdbms\ConnectionManager;
use rdbms\unittest\mock\RegisterMockConnection;
use unittest\Assert;
use util\Properties;

/**
 * Tests for configured connection managers
 *
 * @see   xp://rdbms.ConnectionManager#configure
 * @see   xp://net.xp_framework.unittest.rdbms.ConnectionManagerTest
 */
#[Action(eval: 'new RegisterMockConnection()')]
class ConfiguredConnectionManagerTest extends ConnectionManagerTest {

  /**
   * Returns an instance with a given number of DSNs
   *
   * @param   [:string] dsns
   * @return  rdbms.ConnectionManager
   */
  protected function instanceWith($dsns) {
    $properties= '';
    foreach ($dsns as $name => $dsn) {
      $properties.= '['.$name."]\ndsn=\"".$dsn."\"\n";
    }

    $p= new Properties(null);
    $p->load(new MemoryInputStream($properties));

    $cm= ConnectionManager::getInstance();
    $cm->configure($p);
    return $cm;
  }
}