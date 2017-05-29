<?php namespace rdbms;

/**
 * Represents an SQL statement
 *
 * ```php
 * with ($peer= News::getPeer()); {
 *   $statement= new Statement('select * from news where news_id < 10');
 * 
 *   // Use doSelect()
 *   $objects= $peer->doSelect($statement);
 * 
 *   // Use iteratorFor()
 *   for ($iterator= $peer->iteratorFor($statement); $iterator->hasNext(); ) {
 *     $object= $iterator->next();
 *     // ...
 *   }
 * }
 * ```
 *
 * @test  xp://net.xp_framework.unittest.rdbms.StatementTest
 */
class Statement implements SQLExpression {
  public $statement;
  public $arguments= [];

  /**
   * Constructor
   *
   * @param  string $statement
   * @param  var... $args
   */
  public function __construct($statement, ... $arguments) {
    $this->statement= $statement;
    $this->arguments= $arguments;
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this)."@{\n  ".$this->statement."\n}";
  }
      
  /**
   * test if the Expression is a projection
   *
   * @return  bool
   */
  public function isProjection() {
    return false;
  }

  /**
   * test if the Expression is a join
   *
   * @return  bool
   */
  public function isJoin() {
    return false;
  }

  /**
   * Executes an SQL SELECT statement
   *
   * @param   rdbms.DBConnection conn
   * @param   rdbms.Peer peer
   * @param   rdbms.join.Joinprocessor jp optional
   * @param   bool buffered default TRUE
   * @return  rdbms.ResultSet
   */
  public function executeSelect(DBConnection $conn, Peer $peer, $jp= null, $buffered= true) {
    $statement= preg_replace(
      '/object\(([^\)]+)\)/i', 
      '$1.'.implode(', $1.', array_keys($peer->types)),
      $this->statement
    );

    if ($buffered) {
      return $conn->query($statement, ...$this->arguments);
    } else {
      return $conn->open($statement, ...$this->arguments);
    }
  }
} 
