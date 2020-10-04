<?php namespace rdbms\unittest;
 
use rdbms\{DBConnection, SQLStateException};
use unittest\actions\RuntimeVersion;
use unittest\{Expect, Test};
use util\Date;

/**
 * Test rdbms tokenizer
 *
 * @see   xp://rdbms.StatementFormatter
 */
abstract class TokenizerTest extends \unittest\TestCase {
  protected $fixture= null;

  /**
   * Sets up a Database Object for the test
   *
   * @return  rdbms.DBConnection
   */
  protected abstract function fixture();

  /**
   * Sets up a Database Object for the test
   */
  public function setUp() {
    $this->fixture= $this->fixture();
  }

  #[Test]
  public function doubleQuotedString() {
    $this->assertEquals(
      'select \'Uber\' + \' \' + \'Coder\' as realname',
      $this->fixture->prepare('select "Uber" + " " + "Coder" as realname')
    );
  }

  #[Test]
  public function singleQuotedString() {
    $this->assertEquals(
      'select \'Uber\' + \' \' + \'Coder\' as realname',
      $this->fixture->prepare("select 'Uber' + ' ' + 'Coder' as realname")
    );
  }

  #[Test]
  public function doubleQuotedStringWithEscapes() {
    $this->assertEquals(
      'select \'Quote signs: " \'\' ` \'\'\' as test',
      $this->fixture->prepare('select "Quote signs: "" \' ` \'" as test')
    );
  }

  #[Test]
  public function singleQuotedStringWithEscapes() {
    $this->assertEquals(
      'select \'Quote signs: " \'\' ` \'\'\' as test',
      $this->fixture->prepare("select 'Quote signs: \" '' ` ''' as test")
    );
  }
    
  #[Test]
  public function escapedPercentTokenInString() {
    $this->assertEquals(
      'select * from test where name like \'%.de\'',
      $this->fixture->prepare('select * from test where name like "%%.de"')
    );
  }

  #[Test]
  public function doubleEscapedPercentTokenInString() {
    $this->assertEquals(
      'select * from test where url like \'http://%%20\'',
      $this->fixture->prepare('select * from test where url like "http://%%%%20"')
    );
  }

  #[Test]
  public function escapedPercentTokenInValue() {
    $this->assertEquals(
      'select * from test where url like \'http://%%20\'',
      $this->fixture->prepare('select * from test where url like %s', 'http://%%20')
    );
  }

  #[Test]
  public function percentTokenInString() {
    $this->assertEquals(
      'select * from test where name like \'%.de\'',
      $this->fixture->prepare('select * from test where name like "%.de"')
    );
  }

  #[Test]
  public function unknownTokenInString() {
    $this->assertEquals(
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
    $this->assertEquals(
      'select 1 as intval',
      $this->fixture->prepare('select %d as intval', 1)
    );
  }

  #[Test]
  public function floatTokenWithPrimitive() {
    $this->assertEquals(
      'select 6.1 as floatval',
      $this->fixture->prepare('select %f as floatval', 6.1)
    );
  }

  #[Test]
  public function stringToken() {
    $this->assertEquals(
      'select \'"Hello", Tom\'\'s friend said\' as strval',
      $this->fixture->prepare('select %s as strval', '"Hello", Tom\'s friend said')
    );
  }

  #[Test]
  public function labelToken() {
    $this->assertEquals(
      'select * from \'order\'',
      $this->fixture->prepare('select * from %l', 'order')
    );
  }

  #[Test]
  public function dateToken() {
    $t= new Date('1977-12-14');
    $this->assertEquals(
      "select * from news where date= '1977-12-14 00:00:00'",
      $this->fixture->prepare('select * from news where date= %s', $t)
    );
  }

  #[Test]
  public function timeStampToken() {
    $t= (new Date('1977-12-14'))->getTime();
    $this->assertEquals(
      "select * from news where created= '1977-12-14 00:00:00'",
      $this->fixture->prepare('select * from news where created= %u', $t)
    );
  }

  #[Test]
  public function backslash() {
    $this->assertEquals(
      'select \'Hello \\ \' as strval',
      $this->fixture->prepare('select %s as strval', 'Hello \\ ')
    );
  }
  
  #[Test]
  public function integerArrayToken() {
    $this->assertEquals(
      'select * from news where news_id in ()',
      $this->fixture->prepare('select * from news where news_id in (%d)', [])
    );
    $this->assertEquals(
      'select * from news where news_id in (1, 2, 3)',
      $this->fixture->prepare('select * from news where news_id in (%d)', [1, 2, 3])
    );
  }

  #[Test]
  public function dateArrayToken() {
    $d1= new Date('1977-12-14');
    $d2= new Date('1977-12-15');
    $this->assertEquals(
      "select * from news where created in ('1977-12-14 00:00:00', '1977-12-15 00:00:00')",
      $this->fixture->prepare('select * from news where created in (%s)', [$d1, $d2])
    );
  }
  
  #[Test]
  public function leadingToken() {
    $this->assertEquals(
      'select 1',
      $this->fixture->prepare('%c', 'select 1')
    );
  }
  
  #[Test]
  public function randomAccess() {
    $this->assertEquals(
      'select column from table',
      $this->fixture->prepare('select %2$c from %1$c', 'table', 'column')
    );
  }
  
  #[Test]
  public function passNullValues() {
    $this->assertEquals(
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
    $this->assertEquals(
      'insert into table values (\'value\', \'str%&ing\', \'value\')',
      $this->fixture->prepare('insert into table values (%s, "str%&ing", %s)', 'value', 'value')
    );
  }

  #[Test]
  public function percentSignInValues() {
    $this->assertEquals(
      "select '%20'",
      $this->fixture->prepare('select %s', '%20')
    );
  } 
  
  #[Test]
  public function testHugeIntegerNumber() {
    $this->assertEquals(
      'NULL',
      $this->fixture->prepare('%d', 'Helo 123 Moto')
    );
    $this->assertEquals(
      '0',
      $this->fixture->prepare('%d', '0')
    );
    $this->assertEquals(
      '999999999999999999999999999',
      $this->fixture->prepare('%d', '999999999999999999999999999')
    );
    $this->assertEquals(
      '-999999999999999999999999999',
      $this->fixture->prepare('%d', '-999999999999999999999999999')
    );
  }
  
  #[Test]
  public function testHugeFloatNumber() {
    $this->assertEquals(
      'NULL',
      $this->fixture->prepare('%d', 'Helo 123 Moto')
    );
    $this->assertEquals(
      '0.0',
      $this->fixture->prepare('%d', '0.0')
    );
    $this->assertEquals(
      '0.00000000000000234E03',
      $this->fixture->prepare('%d', '0.00000000000000234E03')
    );
    $this->assertEquals(
      '1232342354362.00000000000000234e-14',
      $this->fixture->prepare('%d', '1232342354362.00000000000000234e-14')
    );
  }

  #[Test]
  public function emptyStringAsNumber() {
    $this->assertEquals('NULL', $this->fixture->prepare('%d', ''));
  }

  #[Test]
  public function dashAsNumber() {
    $this->assertEquals('NULL', $this->fixture->prepare('%d', '-'));
  }

  #[Test]
  public function dotAsNumber() {
    $this->assertEquals('NULL', $this->fixture->prepare('%d', '.'));
  }
 
  #[Test]
  public function plusAsNumber() {
    $this->assertEquals('NULL', $this->fixture->prepare('%d', '+'));
  } 

  #[Test]
  public function trueAsNumber() {
    $this->assertEquals('1', $this->fixture->prepare('%d', true));
  } 

  #[Test]
  public function falseAsNumber() {
    $this->assertEquals('0', $this->fixture->prepare('%d', false));
  } 
}