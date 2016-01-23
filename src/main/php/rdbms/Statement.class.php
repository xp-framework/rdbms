<?php namespace rdbms;

/**
 * Represents an SQL statement
 *
 * <code>
 *  with ($peer= News::getPeer()); {
 *    $statement= new Statement('select * from news where news_id < 10');
 * 
 *    // Use doSelect()
 *    $objects= $peer->doSelect($statement);
 * 
 *    // Use iteratorFor()
 *    for ($iterator= $peer->iteratorFor($statement); $iterator->hasNext(); ) {
 *      $object= $iterator->next();
 *      // ...
 *    }
 *  }
 * </code>
 *
 * @test  xp://net.xp_framework.unittest.rdbms.StatementTest
 */
class Statement extends \lang\Object implements SQLExpression {
  public $arguments= [];

  /**
   * Constructor
   *
   * @param   string format
   * @param   var* args
   */
  public function __construct() {
    $this->arguments= func_get_args();
  }

  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this)."@{\n  ".$this->arguments[0]."\n}";
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
    $this->arguments[0]= preg_replace(
      '/object\(([^\)]+)\)/i', 
      '$1.'.implode(', $1.', array_keys($peer->types)),
      $this->arguments[0]
    );
    return call_user_func_array([$conn, $buffered ? 'query' : 'open'], $this->arguments);
  }
} 
