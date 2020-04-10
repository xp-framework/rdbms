<?php namespace rdbms\mysqlx;

use lang\Enum;
use math\{BigInt, BigFloat};

/**
 * MySQL password functions
 *
 * @see   php://sha1
 * @see   http://forge.mysql.com/wiki/MySQL_Internals_ClientServer_Protocol
 * @test  xp://net.xp_framework.unittest.rdbms.mysql.MySqlPasswordTest
 */
abstract class MySqlPassword extends Enum {
  public static $PROTOCOL_40, $PROTOCOL_41;
  
  static function __static() {
    self::$PROTOCOL_40= new class(0, 'PROTOCOL_40') extends MySqlPassword {
      static function __static() { }
      
      public static function hash($in) {
        $nr= new BigInt(1345345333);
        $nr2= new BigInt(0x12345671);
        $add= new BigInt(7);

        for ($i= 0, $s= strlen($in); $i < $s; $i++) {
          $ord= ord($in[$i]);
          if (0x20 === $ord || 0x09 === $ord) continue;
          $value= $nr->bitwiseAnd(63)->add0($add)->multiply0($ord)->add0($nr->multiply0(0x100));
          $nr= $nr->bitwiseXor($value);
          $nr2= $nr2->multiply0(0x100)->bitwiseXor($nr)->add0($nr2);
          $add= $add->add0($ord);
        }
        return [$nr->bitwiseAnd(0x7FFFFFFF), $nr2->bitwiseAnd(0x7FFFFFFF)];
      }
      
      public function scramble($password, $message) {
        if ('' === $password || null === $password) return '';

        $hp= self::hash($password);
        $hm= self::hash($message);
        $SEED_MAX= 0x3FFFFFFF;

        $seed1= $hp[0]->bitwiseXor($hm[0])->modulo($SEED_MAX);
        $seed2= $hp[1]->bitwiseXor($hm[1])->modulo($SEED_MAX);
        $to= '';
        for ($i= 0, $s= strlen($message); $i < $s; $i++) {
          $seed1= $seed1->multiply0(3)->add0($seed2)->modulo($SEED_MAX);
          $seed2= $seed1->add0($seed2)->add0(33)->modulo($SEED_MAX);
          $div= new BigFloat(bcdiv((string)$seed1, $SEED_MAX));
          $to.= chr($div->multiply(31)->intValue() + 64);
        }
        $seed1= $seed1->multiply0(3)->add0($seed2)->modulo($SEED_MAX);
        $seed2= $seed1->add0($seed2)->add0(33)->modulo($SEED_MAX);

        $div= new BigFloat(bcdiv((string)$seed1, $SEED_MAX));
        $result= $to ^ str_repeat(chr($div->multiply(31)->intValue()), strlen($message));
        return $result;
      }
    };
    self::$PROTOCOL_41= new class(1, 'PROTOCOL_41') extends MySqlPassword {
      static function __static() { }

      public function scramble($password, $message) {
        if ('' === $password || null === $password) return '';

        $stage1= sha1($password, true);
        return sha1($message.sha1($stage1, true), true) ^ $stage1;
      }
    };
  }
  
  /**
   * Scrambles a given password
   *
   * @param   string password
   * @param   string message
   * @return  string
   */
  public abstract function scramble($password, $message);
}