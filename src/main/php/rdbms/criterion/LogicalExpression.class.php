<?php namespace rdbms\criterion;

define('LOGICAL_AND', 'and');
define('LOGICAL_OR',  'or');

/**
 * Logical expression
 */
class LogicalExpression extends \lang\Object implements Criterion {
  public
    $criterions = [],
    $op         = '';

  /**
   * Constructor
   *
   * @param   rdbms.criterion.Criterion[] criterions
   * @param   string op one of the LOGICAL_* constants
   */
  public function __construct($criterions, $op) {
    $this->criterions= $criterions;
    $this->op= $op;
  }

  /**
   * Returns the fragment SQL
   *
   * @param   rdbms.DBConnection conn
   * @param   rdbms.Peer peer
   * @return  string
   * @throws  rdbms.SQLStateException
   */
  public function asSql(\rdbms\DBConnection $conn, \rdbms\Peer $peer) {
    $sql= '';
    for ($i= 0, $s= sizeof($this->criterions); $i < $s; $i++) {
      $sql.= $this->criterions[$i]->asSql($conn, $peer).' '.$this->op.' ';
    }
    return '('.substr($sql, 0, (-1 * strlen($this->op)) - 2).')';
  }

} 
