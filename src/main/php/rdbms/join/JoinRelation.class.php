<?php namespace rdbms\join;


/**
 * Represents a relation between two tables
 * Helper (bean) class for JoinPart and JoinProcessor
 *
 * @see     xp://rdbms.join.JoinPart
 * @see     xp://rdbms.join.JoinPRocessor
 * @purpose rdbms.join
 *
 */
class JoinRelation {
  private
    $source= null,
    $target= null,
    $conditions= [];

  /**
   * Constructor
   *
   * @param   string name
   * @param   string alias
   * @param   string[] optional conditions
   */
  public function __construct(JoinTable $source, JoinTable $target, $conditions= []) {
    $this->source= $source;
    $this->target= $target;
    $this->conditions= $conditions;
  }

 /**
   * Set source
   *
   * @param   rdbms.join.JoinTable source
   */
  public function setSource(JoinTable $source) {
    $this->source= $source;
  }

  /**
   * Get source
   *
   * @return  rdbms.join.JoinTable
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Set target
   *
   * @param   rdbms.join.JoinTable target
   */
  public function setTarget(JoinTable $target) {
    $this->target= $target;
  }

  /**
   * Get target
   *
   * @return  rdbms.join.JoinTable
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Set conditions
   *
   * @param   string[] conditions
   */
  public function setConditions($conditions) {
    $this->conditions= $conditions;
  }

  /**
   * Get conditions
   *
   * @return  string[]
   */
  public function getConditions() {
    return $this->conditions;
  }
}
