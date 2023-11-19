<?php namespace rdbms\unittest\mysql;

use rdbms\mysql\MySQLDBAdapter;
use rdbms\{DBTableAttribute, FieldType};
use test\Assert;
use test\Test;

/**
 * TestCase
 *
 * @see    xp://rdbms.mysql.MySQLDBAdapter
 */
class TableDescriptionTest {

  #[Test]
  public function auto_increment() {
    Assert::equals(
      new DBTableAttribute('contract_id', FieldType::INT, true, false, 8, 0, 0),
      MySQLDBAdapter::tableAttributeFrom([
        'Field'   => 'contract_id',
        'Type'    => 'int(8)',
        'Null'    => '',
        'Key'     => 'PRI',
        'Default' => null,
        'Extra'   => 'auto_increment'
      ])
    );
  }

  #[Test]
  public function unsigned_int() {
    Assert::equals(
      new DBTableAttribute('bz_id', FieldType::INT, false, false, 6, 0, 0),
      MySQLDBAdapter::tableAttributeFrom([
        'Field'   => 'bz_id',
        'Type'    => 'int(6) unsigned',
        'Null'    => '',
        'Key'     => '',
        'Default' => 500,
        'Extra'   => ''
      ])
    );
  }
}