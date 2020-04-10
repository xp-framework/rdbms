<?php namespace rdbms\util;

use lang\System;
use rdbms\DBTable;
use util\Objects;
use util\log\Traceable;
use xml\Tree;

/**
 * Generate an XML representation of a database table
 *
 * @deprecated
 * @see   xp://rdbms.DBTable
 */
class DBXmlGenerator implements Traceable {
  protected
    $cat= null;

  public
    $doc   = null,
    $table = null;

  static function __static() {
    \lang\XPClass::forName('rdbms.DBTableAttribute');  // For global constants
  }

  /**
   * Constructor
   *
   */
  public function __construct() {
    $this->doc= new Tree();
  }

  /**
   * Create XML from a DBTable
   *
   * @param   rdbms.DBTable table
   * @param   string dbhost
   * @param   string database
   * @return  rdbms.util.DBXmlGenerator object
   */    
  public static function createFromTable(DBTable $table, $dbhost, $database) {
    $g= new self();
    $g->doc->root()->setAttribute('created_at', date('r'));
    $g->doc->root()->setAttribute('created_by', System::getProperty('user.name'));
    
    $g->doc->root()->addChild(new \xml\Node('table', null, [
      'name'     => $table->name,
      'dbhost'   => $dbhost,
      'database' => $database
    ]));
    $g->table= $table;
    return $g;
  }
  
  /**
   * Get XML tree
   *
   * @return  xml.Tree
   */    
  public function getTree() {
    $indexes= [];

    // Attributes
    with ($t= $this->doc->root()->nodeAt(0)); {
      if ($attr= $this->table->getFirstAttribute()) do {
        $a= $t->addChild(new \xml\Node('attribute', null, [
          'name'     => trim($attr->getName()),
          'type'     => $attr->getTypeString(),
          'identity' => $attr->isIdentity()  ? 'true' : 'false',
          'typename' => $attr->typeName(),
          'nullable' => $attr->isNullable() ? 'true' : 'false',
        ]));
        
        // Only add length attribute if length is set - "bool" does not
        // have a length, whereas varchar(255) does.
        $attr->getLength() && $a->setAttribute('length', $attr->getLength());
      } while ($attr= $this->table->getNextAttribute());

      // Indexes
      if ($index= $this->table->getFirstIndex()) do {
        $n= $t->addChild(new \xml\Node('index', null, [
          'name'    => trim($index->getName()),
          'unique'  => $index->isUnique() ? 'true' : 'false',
          'primary' => $index->isPrimaryKey() ? 'true' : 'false',
        ]));

        foreach ($index->getKeys() as $key) {
          $n->addChild(new \xml\Node('key', $key));
        }
        if (isset($indexes[implode('|', $index->getKeys())]) && $this->cat) $this->cat->warn('('.implode('|', $index->getKeys()).')', 'has been indexed twice');
        $indexes[implode('|', $index->getKeys())]= true;

      } while ($index= $this->table->getNextIndex());

      // constraints
      $constKeyList= [];
      if ($constraint= $this->table->getFirstForeignKeyConstraint()) do {
        if (isset($constKeyList[$this->constraintKey($constraint)])) {
          $this->cat && $this->cat->warn($this->table->name, 'has a double constraint'."\n".Objects::stringOf($constraint));
          continue;
        }
        $constKeyList[$this->constraintKey($constraint)]= true;
        $cn= $t->addChild(new \xml\Node('constraint', null, [
          'name' => trim($constraint->getName()),
        ]));
        $fgn= $cn->addChild(new \xml\Node('reference', null, [
          'table' => $constraint->getSource(),
          'role'  => DBXMLNamingContext::foreignKeyConstraintName($this->table, $constraint),
        ]));
        foreach ($constraint->getKeys() as $attribute => $sourceattribute) {
          $fgn->addChild(new \xml\Node('key', null, [
            'attribute'       => $attribute,
            'sourceattribute' => $sourceattribute
          ]));
        }

      } while ($constraint= $this->table->getNextForeignKeyConstraint());
    }
    
    return $this->doc;
  }

  /**
   * Get XML source
   *
   * @return  string source
   */
  public function getSource() {
    return $this->getTree()->getSource(false);
  }

  /**
   * Set a trace for debugging
   *
   * @param   util.log.LogCategory cat
   */
  public function setTrace($cat) {
    $this->cat= $cat;
  }

  /**
   * descriptive key for constraint
   *
   * @param   rdbms.DBForeignKeyConstraint
   * @return  string
   */
  private function constraintKey($c) {
    return $c->source.'#'.implode('|', array_keys($c->keys)).'#'.implode('|', array_values($c->keys));
  }
}