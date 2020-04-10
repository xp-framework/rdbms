<?php namespace rdbms\mysql;
use rdbms\SQLDialect;


/**
 * helps to build functions for different SQL servers
 *
 */
class MysqlDialect extends SQLDialect {
  private static
    $dateparts= [
      'dayofyear'   => false,
      'weekday'     => false,
      'millisecond' => false,
    ],
    $implementations= [
      'str_1'        => 'cast(%s as char)',
      'len_1'        => 'length(%s)',
      'getdate_0'    => 'sysdate()',
      'dateadd_3'    => 'timestampadd(%t, %d, %s)',
      'datediff_3'   => 'timestampdiff(%t, %s, %s)',
      'datename_2'   => 'cast(extract(%t from %s) as char)',
      'datepart_2'   => 'extract(%t from %s)',
    ];

  public
    $escape       = "'",
    $escapeRules  = [
      "'"   => "''",
      '\\'  => '\\\\'
    ],
    $escapeT      = '`',
    $escapeTRules = ['`'   => '``'],
    $dateFormat   = 'Y-m-d H:i:s';
      
  /**
   * get a function format string
   *
   * @param   SQLFunction func
   * @return  string
   * @throws  lang.IllegalArgumentException
   */
  public function formatFunction(\rdbms\SQLFunction $func) {
    $func_i= $func->func.'_'.sizeof($func->args);
    switch ($func->func) {
      case 'concat':
      return 'concat('.implode(', ', array_fill(0, sizeof($func->args), '%s')).')';

      default:
      if (isset(self::$implementations[$func_i])) return self::$implementations[$func_i];
      return parent::formatFunction($func);
    }
  }

  /**
   * get a dialect specific datepart
   *
   * @param   string datepart
   * @return  string
   * @throws  lang.IllegalArgumentException
   */
  public function datepart($datepart) {
    $datepart= strToLower($datepart);
    if (!array_key_exists($datepart, self::$dateparts)) return parent::datepart($datepart);
    if (false === self::$dateparts[$datepart]) throw new \lang\IllegalArgumentException('MYSQL does not support datepart '.$datepart);
    return self::$dateparts[$datepart];
  }

  /**
   * build join related part of an SQL query
   *
   * @param   rdbms.join.JoinRelation[] conditions
   * @return  string
   * @throws  lang.IllegalArgumentException
   */
  public function makeJoinBy(Array $conditions) {
    if (0 == sizeof($conditions)) throw new \lang\IllegalArgumentException('conditions can not be empty');
    $querypart= '';
    $first= true;
    foreach ($conditions as $link) {
      if ($first) {
        $first= false;
        $querypart.= sprintf(
          '%s LEFT OUTER JOIN %s on (%s) ',
          $link->getSource()->toSqlString(),
          $link->getTarget()->toSqlString(),
          implode(' and ', $link->getConditions())
        );
      } else {
        $querypart.= sprintf('LEFT JOIN %s on (%s) ', $link->getTarget()->toSqlString(), implode(' and ', $link->getConditions()));
      }
    }
    return $querypart.'where ';
  }
}