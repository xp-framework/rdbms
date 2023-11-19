<?php namespace rdbms\unittest\drivers;

use rdbms\DSN;
use rdbms\sybase\SybaseConnection;

class SybaseTokenizerTest extends TDSTokenizerTest {
    
  /** @return  rdbms.DBConnection */
  protected function fixture() {
    return new SybaseConnection(new DSN('sybase://localhost:1999/'));
  }
}