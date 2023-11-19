<?php namespace rdbms\unittest;

use rdbms\util\DBXmlGenerator;
use rdbms\{DBIndex, DBTable, DriverManager};
use unittest\Assert;
use unittest\{BeforeClass, Test, TestCase};
use xml\XPath;

/**
 * TestCase
 *
 * @see   rdbms.util.DBXmlGenerator
 */
class DBXmlGeneratorTest {
  protected $xpath= null;

  /**
   * Sets up test case
   */
  #[Before]
  public function onlyWithXmlModule() {
    if (!class_exists('xml\Tree')) {
      throw new \unittest\PrerequisitesNotMetError('XML Module not available', NULL, ['loaded']);
    }
  }

  /**
   * Sets up a Database Object for the test
   */
  #[Before]
  public function setUp() {
    $generated= DBXmlGenerator::createFromTable(
      $this->newTable('deviceinfo', [
        'deviceinfo_id' => [DB_ATTRTYPE_INT, 255], 
        'serial_number' => [DB_ATTRTYPE_INT, 16],
        'text'          => [DB_ATTRTYPE_TEXT, 255]
      ]),
      'localhost',
      'FOOBAR'
    );
    $this->xpath= new XPath($generated->getSource());
  }

  /**
   * Helper method which creates a database table
   *
   * @param   string $name
   * @param   string[] $attr
   * @return  rdbms.DBTable object
   */
  public function newTable($name, $attr) {
    $t= new DBTable($name);
    foreach ($attr as $key => $definitions) {
      $t->attributes[]= new \rdbms\DBTableAttribute(
        $key,
        $definitions[0],    // Type
        true,
        false,
        $definitions[1]     // Length
      );
    }
    $t->indexes[]= new DBIndex(
      'PRIMARY',
      ['deviceinfo_id']
    );
    $t->indexes[0]->unique= true;
    $t->indexes[0]->primary= true;
    $t->indexes[]= new DBIndex(
      'deviceinfo_I_serial',
      ['serial_number']
    );
    return $t;
  }

  #[Test]
  public function correctTableNameSet() {
    Assert::equals('deviceinfo', $this->xpath->query('string(/document/table/@name)'));
  }

  #[Test]
  public function correctDatabaseNameSet() {
    Assert::equals('FOOBAR', $this->xpath->query('string(/document/table/@database)'));
  }

  #[Test]
  public function correctTypeSet() {
    Assert::equals('DB_ATTRTYPE_TEXT', $this->xpath->query('string(/document/table/attribute[3]/@type)'));
  }    

  #[Test]
  public function correctTypeNameSet() {
    Assert::equals('string', $this->xpath->query('string(/document/table/attribute[3]/@typename)'));
    Assert::equals('int', $this->xpath->query('string(/document/table/attribute[2]/@typename)'));
  }    

  #[Test]
  public function primaryKeySet() {
    Assert::equals('true', $this->xpath->query('string(/document/table/index[1]/@primary)'));
  }

  #[Test]
  public function primaryKeyNotSet() {
    Assert::equals('false', $this->xpath->query('string(/document/table/index[2]/@primary)'));
  }    

  #[Test]
  public function uniqueKeySet() {
    Assert::equals('true', $this->xpath->query('string(/document/table/index[1]/@unique)'));
  }

  #[Test]
  public function uniqueKeyNotSet() {
    Assert::equals('false', $this->xpath->query('string(/document/table/index[2]/@unique)'));
  }
  
  #[Test]
  public function correctKey() {
    Assert::equals('deviceinfo_id', trim($this->xpath->query('string(/document/table/index[1]/key)')));
  }

  #[Test]
  public function correctKeyName() {
    Assert::equals('PRIMARY', $this->xpath->query('string(/document/table/index[1]/@name)'));
  }

  #[Test]
  public function identitySet() {
    Assert::equals('true', $this->xpath->query('string(/document/table/attribute[1]/@identity)'));
  }

  #[Test]
  public function nullableSet() {
    Assert::equals('false', $this->xpath->query('string(/document/table/attribute[1]/@nullable)'));
  }

  #[Test]
  public function dbhostSet() {
    Assert::equals('localhost', $this->xpath->query('string(/document/table/@dbhost)'));
  }
}