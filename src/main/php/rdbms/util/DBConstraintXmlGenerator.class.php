<?php namespace rdbms\util;

use lang\System;
use rdbms\DBTable;
use util\log\Traceable;
use xml\Tree;

/**
 * Generate the relation map of a database
 *
 * @deprecated
 * @see   xp://rdbms.DBTable
 */
class DBConstraintXmlGenerator implements Traceable {
  protected
    $cat= null,
    $tables= null;

  public
    $doc= null;
    
  /**
   * Constructor
   *
   */
  public function __construct() {
    $this->doc= new Tree();
  }

  /**
   * Create XML map
   *
   * @param   rdbms.DBAdapter and adapter
   * @param   string database
   * @return  rdbms.util.DBConstraintXmlGenerator object
   */    
  public static function createFromDatabase($adapter, $database) {
    $g= new self();
    $g->doc->root()->setAttribute('created_at', date('r'));
    $g->doc->root()->setAttribute('created_by', System::getProperty('user.name'));
    
    $g->doc->root()->addChild(new \xml\Node('database', null, [
      'database' => $database
    ]));
    
    $g->tables= DBTable::getByDatabase($adapter, $database);
    return $g;
  }

  /**
   * Get XML tree
   *
   * @return  xml.Tree
   */    
  public function getTree() {
    foreach ($this->tables as $t) {
      $constKeyList= [];
      $tn= $this->doc->root()->nodeAt(0)->addChild(new \xml\Node('table', null, [
        'name' => $t->name,
      ]));

      if ($constraint= $t->getFirstForeignKeyConstraint()) do {
        if (isset($constKeyList[$this->constraintKey($constraint)])) {
          $this->cat && $this->cat->warn($t->name, 'has a double constraint'."\n".\xp::stringOf($constraint));
          continue;
        }
        $constKeyList[$this->constraintKey($constraint)]= true;
        $cn= $tn->addChild(new \xml\Node('constraint', null, [
          'name' => trim($constraint->getName()),
        ]));
        $fgn= $cn->addChild(new \xml\Node('reference', null, [
          'table' => $constraint->getSource(),
          'role'  => DBXMLNamingContext::referencingForeignKeyConstraintName($t, $constraint),
        ]));
        foreach ($constraint->getKeys() as $attribute => $sourceattribute) {
          $fgn->addChild(new \xml\Node('key', null, [
            'attribute'       => $attribute,
            'sourceattribute' => $sourceattribute,
          ]));
        }

      } while ($constraint= $t->getNextForeignKeyConstraint());
    }
    return $this->doc;
  }

  /**
   * Get XML source
   *
   * @return  string xml representation
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
