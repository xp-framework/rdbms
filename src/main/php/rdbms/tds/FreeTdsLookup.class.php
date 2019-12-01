<?php namespace rdbms\tds;

use io\File;

/**
 * Lookup host name and port to connect to by freetds.conf file
 *
 * @test    xp://net.xp_framework.unittest.rdbms.tds.FreeTdsLookupTest
 * @test    xp://net.xp_framework.unittest.rdbms.tds.FreeTdsConfigLocationTest
 */
class FreeTdsLookup implements ConnectionLookup {
  protected $conf= null;
  
  /**
   * Creates a new sql.conf lookup instance with a given file. If
   * the file is omitted, the file will be checked for in the following
   * locations:
   *
   * <ul>
   *   <li>ENV{FREETDSCONF}</li>
   *   <li>ENV{HOME}/.freetds.conf</li>
   *   <li>/etc/freetds.conf</li>
   *   <li>/etc/freetds/freetds.conf</li>
   *   <li>/usr/local/etc/freetds.conf</li>
   * </ul>
   *
   * @param   io.File conf
   */
  public function __construct($conf= null) {
    $this->conf= $conf;
  }
  
  /**
   * Parse freetds.conf file and return sections
   *
   * @return  [:[:string]] sections
   */
  protected function parse() {
    $this->conf->open(File::READ);
    $section= null;
    $sections= [];
    while (false !== ($line= $this->conf->readLine())) {
      $line= trim($line);
      if ('' === $line || ';' === $line[0] || '#' === $line[0]) {
        continue;
      } else if ('[' === $line[0]) {
        $section= strtolower(trim($line, '[]'));
      } else if (false !== ($p= strpos($line, '='))) {
        $key= trim(substr($line, 0, $p));
        $value= trim(substr($line, $p+ 1));
        $sections[$section][$key]= $value;
      }
    }
    $this->conf->close();
    return $sections;
  }

  /**
   * Locate config file
   *
   * @return  io.File
   */
  protected function locateConf() {
    foreach ([
      getenv('FREETDSCONF'),
      getenv('HOME').'/.freetds.conf',
      '/etc/freetds/freetds.conf',
      '/etc/freetds.conf',
      '/usr/local/etc/freetds.conf'
    ] as $location) {
      if (empty($location)) continue;
      $f= new File($location);
      if ($f->exists()) return $f;
    }
    return null;
  }

  /**
   * Look up DSN. Reparses config file every time its called.
   *
   * @param   rdbms.DSN dsn
   */
  public function lookup($dsn) {
    if (null === $this->conf) {
      if (null === ($this->conf= $this->locateConf())) return;
    } else {
      if (!$this->conf->exists()) return;
    }

    $host= strtolower($dsn->getHost());
    $sections= $this->parse();
    if (!isset($sections[$host])) return;

    $dsn->url->setHost($sections[$host]['host']);
    $dsn->url->setPort((int)$sections[$host]['port']);
  }
}
