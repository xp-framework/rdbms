<?php namespace rdbms\unittest;

use rdbms\{DBConnection, SQLStateException};
use test\verify\Runtime;
use test\{Assert, Before, Expect, Test};
use util\{Date, UUID};

abstract class TokenizerTest {
  protected $fixture= null;

  /**
   * Sets up a Database Object for the test
   *
   * @return  rdbms.DBConnection
   */
  protected abstract function fixture();

  #[Before]
  public function setUp() {
    $this->fixture= $this->fixture();
  }

  #[Test]
  public function doubleQuotedString() {
    Assert::equals(
      'select \'Uber\' + \' \' + \'Coder\' as realname',
      $this->fixture->prepare('select "Uber" + " " + "Coder" as realname')
    );
  }

  #[Test]
  public function singleQuotedString() {
    Assert::equals(
      'select \'Uber\' + \' \' + \'Coder\' as realname',
      $this->fixture->prepare("select 'Uber' + ' ' + 'Coder' as realname")
    );
  }

  #[Test]
  public function doubleQuotedStringWithEscapes() {
    Assert::equals(
      'select \'Quote signs: " \'\' ` \'\'\' as test',
      $this->fixture->prepare('select "Quote signs: "" \' ` \'" as test')
    );
  }

  #[Test]
  public function singleQuotedStringWithEscapes() {
    Assert::equals(
      'select \'Quote signs: " \'\' ` \'\'\' as test',
      $this->fixture->prepare("select 'Quote signs: \" '' ` ''' as test")
    );
  }
    
  #[Test]
  public function escapedPercentTokenInString() {
    Assert::equals(
      'select * from test where name like \'%.de\'',
      $this->fixture->prepare('select * from test where name like "%%.de"')
    );
  }

  #[Test]
  public function doubleEscapedPercentTokenInString() {
    Assert::equals(
      'select * from test where url like \'http://%%20\'',
      $this->fixture->prepare('select * from test where url like "http://%%%%20"')
    );
  }

  #[Test]
  public function escapedPercentTokenInValue() {
    Assert::equals(
      'select * from test where url like \'http://%%20\'',
      $this->fixture->prepare('select * from test where url like %s', 'http://%%20')
    );
  }

  #[Test]
  public function percentTokenInString() {
    Assert::equals(
      'select * from test where name like \'%.de\'',
      $this->fixture->prepare('select * from test where name like "%.de"')
    );
  }

  #[Test]
  public function unknownTokenInString() {
    Assert::equals(
      'select * from test where name like \'%X\'',
      $this->fixture->prepare('select * from test where name like "%X"')
    );
  }

  #[Test, Expect(SQLStateException::class)]
  public function unknownToken() {
    $this->fixture->prepare('select * from test where name like %X');
  }

  #[Test, Expect(SQLStateException::class)]
  public function unclosedDoubleQuotedString() {
    $this->fixture->prepare('select * from test where name = "unclosed');
  }

  #[Test, Expect(SQLStateException::class)]
  public function unclosedDoubleQuotedStringEndingWithEscape() {
    $this->fixture->prepare('select * from test where name = "unclosed""');
  }
  
  #[Test, Expect(SQLStateException::class)]
  public function unclosedSingleQuotedString() {
    $this->fixture->prepare("select * from test where name = 'unclosed");
  }

  #[Test, Expect(SQLStateException::class)]
  public function unclosedSingleQuotedStringEndingWithEscape() {
    $this->fixture->prepare("select * from test where name = 'unclosed''");
  }
  
  #[Test]
  public function numberTokenWithPrimitive() {
    Assert::equals(
      'select 1 as intval',
      $this->fixture->prepare('select %d as intval', 1)
    );
  }

  #[Test]
  public function floatTokenWithPrimitive() {
    Assert::equals(
      'select 6.1 as floatval',
      $this->fixture->prepare('select %f as floatval', 6.1)
    );
  }

  #[Test]
  public function stringToken() {
    Assert::equals(
      'select \'"Hello", Tom\'\'s friend said\' as strval',
      $this->fixture->prepare('select %s as strval', '"Hello", Tom\'s friend said')
    );
  }

  #[Test]
  public function labelToken() {
    Assert::equals(
      'select * from \'order\'',
      $this->fixture->prepare('select * from %l', 'order')
    );
  }

  #[Test]
  public function dateToken() {
    $t= new Date('1977-12-14');
    Assert::equals(
      "select * from news where date= '1977-12-14 00:00:00'",
      $this->fixture->prepare('select * from news where date= %s', $t)
    );
  }

  #[Test]
  public function uuidToken() {
    $t= new UUID('b1e2f772-ae5b-4eba-aca5-174c8d0f4cc1');
    Assert::equals(
      "select * from news where id= 'b1e2f772-ae5b-4eba-aca5-174c8d0f4cc1'",
      $this->fixture->prepare('select * from news where id= %s', $t)
    );
  }

  #[Test]
  public function timeStampToken() {
    $t= (new Date('1977-12-14'))->getTime();
    Assert::equals(
      "select * from news where created= '1977-12-14 00:00:00'",
      $this->fixture->prepare('select * from news where created= %u', $t)
    );
  }

  #[Test]
  public function backslash() {
    Assert::equals(
      'select \'Hello \\ \' as strval',
      $this->fixture->prepare('select %s as strval', 'Hello \\ ')
    );
  }
  
  #[Test]
  public function integerArrayToken() {
    Assert::equals(
      'select * from news where news_id in ()',
      $this->fixture->prepare('select * from news where news_id in (%d)', [])
    );
    Assert::equals(
      'select * from news where news_id in (1, 2, 3)',
      $this->fixture->prepare('select * from news where news_id in (%d)', [1, 2, 3])
    );
  }

  #[Test]
  public function dateArrayToken() {
    $d1= new Date('1977-12-14');
    $d2= new Date('1977-12-15');
    Assert::equals(
      "select * from news where created in ('1977-12-14 00:00:00', '1977-12-15 00:00:00')",
      $this->fixture->prepare('select * from news where created in (%s)', [$d1, $d2])
    );
  }
  
  #[Test]
  public function leadingToken() {
    Assert::equals(
      'select 1',
      $this->fixture->prepare('%c', 'select 1')
    );
  }
  
  #[Test]
  public function randomAccess() {
    Assert::equals(
      'select column from table',
      $this->fixture->prepare('select %2$c from %1$c', 'table', 'column')
    );
  }
  
  #[Test]
  public function passNullValues() {
    Assert::equals(
      'select NULL from NULL',
      $this->fixture->prepare('select %2$c from %1$c', null, null)
    );
  }
  
  #[Test, Expect(SQLStateException::class)]
  public function accessNonexistant() {
    $this->fixture->prepare('%2$c', null);
  }

  #[Test]
  public function percentSignInPrepareString() {
    Assert::equals(
      'insert into table values (\'value\', \'str%&ing\', \'value\')',
      $this->fixture->prepare('insert into table values (%s, "str%&ing", %s)', 'value', 'value')
    );
  }

  #[Test]
  public function percentSignInValues() {
    Assert::equals(
      "select '%20'",
      $this->fixture->prepare('select %s', '%20')
    );
  } 
  
  #[Test]
  public function testHugeIntegerNumber() {
    Assert::equals(
      'NULL',
      $this->fixture->prepare('%d', 'Helo 123 Moto')
    );
    Assert::equals(
      '0',
      $this->fixture->prepare('%d', '0')
    );
    Assert::equals(
      '999999999999999999999999999',
      $this->fixture->prepare('%d', '999999999999999999999999999')
    );
    Assert::equals(
      '-999999999999999999999999999',
      $this->fixture->prepare('%d', '-999999999999999999999999999')
    );
  }
  
  #[Test]
  public function testHugeFloatNumber() {
    Assert::equals(
      'NULL',
      $this->fixture->prepare('%d', 'Helo 123 Moto')
    );
    Assert::equals(
      '0.0',
      $this->fixture->prepare('%d', '0.0')
    );
    Assert::equals(
      '0.00000000000000234E03',
      $this->fixture->prepare('%d', '0.00000000000000234E03')
    );
    Assert::equals(
      '1232342354362.00000000000000234e-14',
      $this->fixture->prepare('%d', '1232342354362.00000000000000234e-14')
    );
  }

  #[Test]
  public function emptyStringAsNumber() {
    Assert::equals('NULL', $this->fixture->prepare('%d', ''));
  }

  #[Test]
  public function dashAsNumber() {
    Assert::equals('NULL', $this->fixture->prepare('%d', '-'));
  }

  #[Test]
  public function dotAsNumber() {
    Assert::equals('NULL', $this->fixture->prepare('%d', '.'));
  }
 
  #[Test]
  public function plusAsNumber() {
    Assert::equals('NULL', $this->fixture->prepare('%d', '+'));
  } 

  #[Test]
  public function trueAsNumber() {
    Assert::equals('1', $this->fixture->prepare('%d', true));
  } 

  #[Test]
  public function falseAsNumber() {
    Assert::equals('0', $this->fixture->prepare('%d', false));
  } 
}