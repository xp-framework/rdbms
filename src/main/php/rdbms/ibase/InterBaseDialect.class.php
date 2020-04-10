<?php namespace rdbms\ibase;

use rdbms\SQLDialect;


/**
 * Helps to build functions for InterBase/Firebird SQL servers
 *
 */
class InterBaseDialect extends SQLDialect {
  private static
    $dateparts= [
      'microsecond' => false,
    ],
    $implementations= [
      'str_1'      => 'convert(varchar, %s)',
      'cast_2'     => 'convert(%2$c, %1$s)',
      'atan_2'     => 'atn2(%d, %d)',
      'ceil_1'     => 'ceiling(%d)',
      'degrees_1'  => 'convert(float, degrees(%d))',
      'radians_1'  => 'convert(float, radians(%d))',
      'sign_1'     => 'convert(int, sign(%d))',
    ];
    
  public
    $escape       = "'",
    $escapeRules  = ["'" => "''"],
    $escapeT      = "'",
    $escapeTRules = ["'" => "''"],
    $dateFormat   = 'Y-m-d H:i:s';
      
  /**
   * Get a function format string
   *
   * @param   SQLFunction func
   * @return  string
   * @throws  lang.IllegalArgumentException
   */
  public function formatFunction(\rdbms\SQLFunction $func) {
    $func_i= $func->func.'_'.sizeof($func->args);
    switch ($func->func) {
      case 'concat':
        return '('.implode(' + ', array_fill(0, sizeof($func->args), '%s')).')';

      default:
        if (isset(self::$implementations[$func_i])) return self::$implementations[$func_i];
      return parent::formatFunction($func);
    }
  }

  /**
   * Get a dialect specific datepart
   *
   * @param   string datepart
   * @return  string
   * @throws  lang.IllegalArgumentException
   */
  public function datepart($datepart) {
    $datepart= strtolower($datepart);
    if (!array_key_exists($datepart, self::$dateparts)) return parent::datepart($datepart);
    if (false === self::$dateparts[$datepart]) throw new \lang\IllegalArgumentException('Sybase does not support datepart '.$datepart);
    return self::$dateparts[$datepart];
  }

  /**
   * Build join related part of an SQL query
   *
   * @param   rdbms.join.JoinRelation[] conditions
   * @return  string
   * @throws  lang.IllegalArgumentException
   */
  public function makeJoinBy(Array $conditions) {
    if (0 == sizeof($conditions)) throw new \lang\IllegalArgumentException('Conditions cannot be empty');
    $tableString= current($conditions)->getSource()->toSqlString();
    $conditionString= '';

    foreach ($conditions as $link) {
      $tableString.= sprintf(', %s', $link->getTarget()->toSqlString());
      foreach ($link->getConditions() as $condition) $conditionString.= str_replace('=', '*=', $condition).' and ';
    }
    return $tableString.' where '.$conditionString;
  }
}