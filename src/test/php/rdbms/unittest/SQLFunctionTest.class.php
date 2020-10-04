<?php namespace rdbms\unittest;

use rdbms\criterion\Restrictions;
use rdbms\mysql\MySQLConnection;
use rdbms\pgsql\PostgreSQLConnection;
use rdbms\sqlite3\SQLite3Connection;
use rdbms\sybase\SybaseConnection;
use rdbms\unittest\dataset\Job;
use rdbms\{Criteria, SQLFunctions};
use unittest\{Test, TestCase};
use util\Date;

/**
 * TestCase
 *
 * @see   xp://rdbms.SQLFunction
 */
class SQLFunctionTest extends TestCase {
  public
    $syconn = null,
    $myconn = null,
    $sqconn = null,
    $pgconn = null,
    $peer   = null;
    
  /**
   * Sets up a Database Object for the test
   *
   */
  public function setUp() {
    $this->syconn= new SybaseConnection(new \rdbms\DSN('sybase://localhost:1999/'));
    $this->myconn= new MySQLConnection(new \rdbms\DSN('mysql://localhost/'));
    $this->pgconn= new PostgreSQLConnection(new \rdbms\DSN('pgsql://localhost/'));
    $this->sqconn= new SQLite3Connection(new \rdbms\DSN('sqlite://tmpdir/tmpdb'));
    $this->peer= Job::getPeer();
  }
  
  /**
   * Helper method that will call toSQL() on the passed criteria and
   * compare the resulting string to the expected string.
   *
   * @param   string mysql
   * @param   string sysql
   * @param   string pgsql
   * @param   string sqlite
   * @param   rdbms.Criteria criteria
   * @throws  unittest.AssertionFailedError
   */
  protected function assertSql($mysql, $sysql, $pgsql, $sqlite, $criteria) {
    $this->assertEquals('mysql: '.$mysql,  'mysql: '.trim($criteria->toSQL($this->myconn, $this->peer), ' '));
    $this->assertEquals('sybase: '.$sysql, 'sybase: '.trim($criteria->toSQL($this->syconn, $this->peer), ' '));
    $this->assertEquals('pgsql: '.$pgsql, 'pgsql: '.trim($criteria->toSQL($this->pgconn, $this->peer), ' '));
    $this->assertEquals('sqlite: '.$sqlite, 'sqlite: '.trim($criteria->toSQL($this->sqconn, $this->peer), ' '));
  }
  
  /**
   * Helper method that will call projection() on the passed criteria and
   * compare the resulting string to the expected string.
   *
   * @param   string mysql
   * @param   string sysql
   * @param   string pgsql
   * @param   string sqlite
   * @param   rdbms.Criteria criteria
   * @throws  unittest.AssertionFailedError
   */
  protected function assertProjection($mysql, $sysql, $pgsql, $sqlite, $criteria) {
    $this->assertEquals('mysql: '.$mysql,  'mysql: '.trim($criteria->projections($this->myconn, $this->peer), ' '));
    $this->assertEquals('sybase: '.$sysql, 'sybase: '.trim($criteria->projections($this->syconn, $this->peer), ' '));
    $this->assertEquals('pgsql: '.$pgsql, 'pgsql: '.trim($criteria->projections($this->pgconn, $this->peer), ' '));
    $this->assertEquals('sqlite: '.$sqlite, 'sqlite: '.trim($criteria->projections($this->sqconn, $this->peer), ' '));
  }
  
  #[Test]
  function columnTest() {
    $this->assertEquals(
      'job_id',
      Job::column('job_id')->getName()
    );
  }

  #[Test]
  function projectionTest() {
    $this->assertProjection(
      'day(valid_from)',
      'day(valid_from)',
      'day(valid_from)',
      'php(\'idate\', \'d\', php(\'strtotime\', valid_from))',
      (new Criteria())->setProjection(SQLFunctions::day(Job::column('valid_from')))
    );
  }
    
  #[Test]
  function prepareProjectionTest() {
    $this->assertEquals(
      '- datepart(hour, valid_from) -',
      $this->syconn->prepare('- %s -', SQLFunctions::datepart('hour', Job::column('valid_from')))
    );
    $this->assertEquals(
      '- extract(hour from valid_from) -',
      $this->myconn->prepare('- %s -', SQLFunctions::datepart('hour', Job::column('valid_from')))
    );
    $this->assertEquals(
      '- datepart(hour, valid_from) -',
      $this->pgconn->prepare('- %s -', SQLFunctions::datepart('hour', Job::column('valid_from')))
    );
    $this->assertEquals(
      '- php(\'idate\', "H", php(\'strtotime\', valid_from)) -',
      $this->sqconn->prepare('- %s -', SQLFunctions::datepart('hour', Job::column('valid_from')))
    );
  }
    
  #[Test]
  function stringFunctionTest() {
    $this->assertProjection(
      'ascii(\'a\') as `asciiTest`',
      'ascii(\'a\') as \'asciiTest\'',
      'ascii(\'a\') as "asciiTest"',
      'php(\'ord\', \'a\') as \'asciiTest\'',
      (new Criteria())->setProjection(SQLFunctions::ascii('a'), 'asciiTest')
    );
    $this->assertProjection(
      'char(97)',
      'char(97)',
      'char(97)',
      'php(\'chr\', 97)',
      (new Criteria())->setProjection(SQLFunctions::char('97'))
    );
    $this->assertProjection(
      'length(\'aaaaaaa\')',
      'len(\'aaaaaaa\')',
      'len(\'aaaaaaa\')',
      'php(\'strlen\', \'aaaaaaa\')',
      (new Criteria())->setProjection(SQLFunctions::len('aaaaaaa'))
    );
    $this->assertProjection(
      'reverse(\'abcdefg\')',
      'reverse(\'abcdefg\')',
      'reverse(\'abcdefg\')',
      'php(\'strrev\', \'abcdefg\')',
      (new Criteria())->setProjection(SQLFunctions::reverse('abcdefg'))
    );
    $this->assertProjection(
      'space(4)',
      'space(4)',
      'space(4)',
      'php(\'str_repeat\', \' \', 4)',
      (new Criteria())->setProjection(SQLFunctions::space(4))
    );
    $this->assertProjection(
      'soundex(\'kawabanga\')',
      'soundex(\'kawabanga\')',
      'soundex(\'kawabanga\')',
      'php(\'soundex\', \'kawabanga\')',
      (new Criteria())->setProjection(SQLFunctions::soundex('kawabanga'))
    );
  }
    
  #[Test]
  function concatStringTest() {
    $this->assertProjection(
      'concat(\'aa\', cast(sysdate() as char), \'cc\') as `concatTest`',
      '(\'aa\' + convert(varchar, getdate()) + \'cc\') as \'concatTest\'',
      '(\'aa\' || str(getdate()) || \'cc\') as "concatTest"',
      '\'aa\' || php(\'strval\', php(\'date\', \'Y-m-d H:i:s\', php(\'time\'))) || \'cc\' as \'concatTest\'',
      (new Criteria())->setProjection(SQLFunctions::concat('aa', SQLFunctions::str(SQLFunctions::getdate()), 'cc'), 'concatTest')
    );
  }
    
  #[Test]
  function dateFunctionTest() {
    $date= new Date();
    $myDate= $date->toString($this->myconn->getFormatter()->dialect->dateFormat);
    $syDate= $date->toString($this->syconn->getFormatter()->dialect->dateFormat);
    $pgDate= $date->toString($this->pgconn->getFormatter()->dialect->dateFormat);
    $sqDate= $date->toString($this->sqconn->getFormatter()->dialect->dateFormat);
    $this->assertProjection(
      'cast(sysdate() as char)',
      'convert(varchar, getdate())',
      'str(getdate())',
      'php(\'strval\', php(\'date\', \'Y-m-d H:i:s\', php(\'time\')))',
      (new Criteria())->setProjection(SQLFunctions::str(SQLFunctions::getdate()))
    );
    $this->assertProjection(
      'cast(timestampadd(month, -4, sysdate()) as char)',
      'convert(varchar, dateadd(month, -4, getdate()))',
      'str(dateadd(month, -4, getdate()))',
      'php(\'strval\', dateadd("m", -4, php(\'date\', \'Y-m-d H:i:s\', php(\'time\'))))',
      (new Criteria())->setProjection(SQLFunctions::str(SQLFunctions::dateadd('month', '-4', SQLFunctions::getdate())))
    );
    $this->assertProjection(
      'timestampdiff(second, timestampadd(day, -4, sysdate()), sysdate())',
      'datediff(second, dateadd(day, -4, getdate()), getdate())',
      'datediff(second, dateadd(day, -4, getdate()), getdate())',
      'datediff_not_implemented',
      (new Criteria())->setProjection(SQLFunctions::datediff('second', SQLFunctions::dateadd('day', '-4', SQLFunctions::getdate()), SQLFunctions::getdate()))
    );
    $this->assertProjection(
      'cast(extract(hour from sysdate()) as char)',
      'datename(hour, getdate())',
      'datename(hour, getdate())',
      'php(\'strval\', php(\'idate\', "H", php(\'strtotime\', php(\'date\', \'Y-m-d H:i:s\', php(\'time\')))))',
      (new Criteria())->setProjection(SQLFunctions::datename('hour', SQLFunctions::getdate()))
    );
    $this->assertProjection(
      'extract(hour from \''.$myDate.'\')',
      'datepart(hour, \''.$syDate.'\')',
      'datepart(hour, \''.$pgDate.'\')',
      'php(\'idate\', "H", php(\'strtotime\', \''.$sqDate.'\'))',
      (new Criteria())->setProjection(SQLFunctions::datepart('hour', $date))
    );
  }
    
  #[Test]
  function mathArithFunctionTest() {
    $this->assertProjection(
      'abs(-6)',
      'abs(-6)',
      'abs(-6)',
      'php(\'abs\', -6)',
      (new Criteria())->setProjection(SQLFunctions::abs(-6))
    );
    $this->assertProjection(
      'ceil(5.1)',
      'ceiling(5.1)',
      'ceil(5.1)',
      'php(\'ceil\', 5.1)',
      (new Criteria())->setProjection(SQLFunctions::ceil(5.1))
    );
    $this->assertProjection(
      'floor(5.7)',
      'floor(5.7)',
      'floor(5.7)',
      'php(\'floor\', 5.7)',
      (new Criteria())->setProjection(SQLFunctions::floor(5.7))
    );
    $this->assertProjection(
      'exp(log(1))',
      'exp(log(1))',
      'exp(log(1))',
      'php(\'exp\', php(\'log\', 1))',
      (new Criteria())->setProjection(SQLFunctions::exp(SQLFunctions::log(1)))
    );
    $this->assertProjection(
      'log10(power(10, 5))',
      'log10(power(10, 5))',
      'log10(power(10, 5))',
      'php(\'log10\', php(\'pow\', 10, 5))',
      (new Criteria())->setProjection(SQLFunctions::log10(SQLFunctions::power(10, 5)))
    );
    $this->assertProjection(
      'power(10, log10(5))',
      'power(10, log10(5))',
      'power(10, log10(5))',
      'php(\'pow\', 10, php(\'log10\', 5))',
      (new Criteria())->setProjection(SQLFunctions::power(10, SQLFunctions::log10(5)))
    );
    $this->assertProjection(
      'round(1.5, 0) as `roundtest1`, round(1.49, 0) as `roundtest2`, round(1.49, 1) as `roundtest3`',
      'round(1.5, 0) as \'roundtest1\', round(1.49, 0) as \'roundtest2\', round(1.49, 1) as \'roundtest3\'',
      'round(1.5, 0) as "roundtest1", round(1.49, 0) as "roundtest2", round(1.49, 1) as "roundtest3"',
      'php(\'round\', 1.5, 0) as \'roundtest1\', php(\'round\', 1.49, 0) as \'roundtest2\', php(\'round\', 1.49, 1) as \'roundtest3\'',
      (new Criteria())->setProjection(\rdbms\criterion\Projections::ProjectionList()
        ->add(SQLFunctions::round(1.50),    'roundtest1')
        ->add(SQLFunctions::round(1.49),    'roundtest2')
        ->add(SQLFunctions::round(1.49, 1), 'roundtest3')
      )
    );
    $this->assertProjection(
      'sign(-7) as `signTest1`, sign(0) as `signTest2`, sign(4) as `signTest3`',
      'convert(int, sign(-7)) as \'signTest1\', convert(int, sign(0)) as \'signTest2\', convert(int, sign(4)) as \'signTest3\'',
      'sign(-7) as "signTest1", sign(0) as "signTest2", sign(4) as "signTest3"',
      'sign(-7) as \'signTest1\', sign(0) as \'signTest2\', sign(4) as \'signTest3\'',
      (new Criteria())->setProjection(\rdbms\criterion\Projections::ProjectionList()
        ->add(SQLFunctions::sign(-7), 'signTest1')
        ->add(SQLFunctions::sign(0),  'signTest2')
        ->add(SQLFunctions::sign(4),  'signTest3')
      )
    );
  }
    
  #[Test]
  function mathTrigFunctionTest() {
    $this->assertProjection(
      'cot(45)',
      'cot(45)',
      'cot(45)',
      'php(\'tan\', php(\'pi\') / 2 - 45)',
      (new Criteria())->setProjection(SQLFunctions::cot(45))
    );
    $this->assertProjection(
      'pi()',
      'pi()',
      'pi()',
      'php(\'pi\')',
      (new Criteria())->setProjection(SQLFunctions::pi())
    );
    $this->assertProjection(
      'acos(cos(0.125))',
      'acos(cos(0.125))',
      'acos(cos(0.125))',
      'php(\'acos\', php(\'cos\', 0.125))',
      (new Criteria())->setProjection(SQLFunctions::acos(SQLFunctions::cos(0.125)))
    );
    $this->assertProjection(
      'asin(sin(0.125))',
      'asin(sin(0.125))',
      'asin(sin(0.125))',
      'php(\'asin\', php(\'sin\', 0.125))',
      (new Criteria())->setProjection(SQLFunctions::asin(SQLFunctions::sin(0.125)))
    );
    $this->assertProjection(
      'atan(tan(0.125))',
      'atan(tan(0.125))',
      'atan(tan(0.125))',
      'php(\'atan\', php(\'tan\', 0.125))',
      (new Criteria())->setProjection(SQLFunctions::atan(SQLFunctions::tan(0.125)))
    );
    $this->assertProjection(
      'atan2(tan(0.125), 0)',
      'atn2(tan(0.125), 0)',
      'atan2(tan(0.125), 0)',
      'php(\'atan2\', php(\'tan\', 0.125), 0)',
      (new Criteria())->setProjection(SQLFunctions::atan(SQLFunctions::tan(0.125), 0))
    );
    $this->assertProjection(
      'degrees(pi())',
      'convert(float, degrees(pi()))',
      'degrees(pi())',
      'php(\'rad2deg\', php(\'pi\'))',
      (new Criteria())->setProjection(SQLFunctions::degrees(SQLFunctions::pi()))
    );
    $this->assertProjection(
      'radians(degrees(90))',
      'convert(float, radians(convert(float, degrees(90))))',
      'radians(degrees(90))',
      'php(\'deg2rad\', php(\'rad2deg\', 90))',
      (new Criteria())->setProjection(SQLFunctions::radians(SQLFunctions::degrees(90)))
    );
    $this->assertProjection(
      'radians(degrees(90))',
      'convert(float, radians(convert(float, degrees(90))))',
      'radians(degrees(90))',
      'php(\'deg2rad\', php(\'rad2deg\', 90))',
      (new Criteria())->setProjection(SQLFunctions::radians(SQLFunctions::degrees(90)))
    );
  }
    
  #[Test]
  function randFunctionTest() {
    $this->assertProjection(
      'rand()',
      'rand()',
      'random()',
      'php(\'rand\')',
      (new Criteria())->setProjection(SQLFunctions::rand())
    );
  }
    
  #[Test]
  function castFunctionTest() {
    $this->assertProjection(
      'cast(\'345\' as decimal)',
      'convert(decimal, \'345\')',
      'cast(\'345\' as decimal)',
      'cast(\'345\' as decimal)',
      (new Criteria())->setProjection(SQLFunctions::cast('345', 'decimal'))
    );
    $this->assertProjection(
      'cast(job_id as char)',
      'convert(char, job_id)',
      'cast(job_id as char)',
      'cast(job_id as char)',
      (new Criteria())->setProjection(SQLFunctions::cast(Job::column('job_id'), 'char'))
    );
  }
    
  #[Test]
  function restrictionTest() {
    $this->assertSQL(
      'where job_id = ceil(asin(sin(0.125)))',
      'where job_id = ceiling(asin(sin(0.125)))',
      'where job_id = ceil(asin(sin(0.125)))',
      'where job_id = php(\'ceil\', php(\'asin\', php(\'sin\', 0.125)))',
      (new Criteria())->add(Restrictions::equal('job_id', SQLFunctions::ceil(SQLFunctions::asin(SQLFunctions::sin(0.125)))))
    );
    $this->assertSQL(
      'where job_id = ceil(asin(sin(0.125)))',
      'where job_id = ceiling(asin(sin(0.125)))',
      'where job_id = ceil(asin(sin(0.125)))',
      'where job_id = php(\'ceil\', php(\'asin\', php(\'sin\', 0.125)))',
      (new Criteria())->add(Restrictions::equal(Job::column('job_id'), SQLFunctions::ceil(SQLFunctions::asin(SQLFunctions::sin(0.125)))))
    );
  }
}