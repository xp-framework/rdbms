<?php namespace rdbms;

use rdbms\DSN;
use util\Configurable;

/**
 * ConnectionManager holds connections to databases
 *
 * @test    xp://net.xp_framework.rdbms.ConnectionManagerTest
 */
class ConnectionManager implements Configurable {
  protected static $instance= null;
  public $pool= [];

  static function __static() {
    self::$instance= new self();
  }

  /**
   * Constructor.
   *
   */
  protected function __construct() {
  }

  /**
   * Return the ConnectionManager's instance
   * 
   * @return  rdbms.ConnectionManager
   */
  public static function getInstance() {
    return self::$instance;
  }
  
  /**
   * Configure this ConnectionManager
   *
   * A sample configuration file:
   * <pre>
   * [caffeine]
   * dsn="sybase://news:enieffac@gurke/CAFFEINE?autoconnect=1"
   *
   * [caffeine.dbo]
   * dsn="sybase://timm:binford@gurke/CAFFEINE?autoconnect=1"
   * </pre>
   *
   * @param   util.Properties properties
   * @return  bool
   * @throws  rdbms.DriverNotSupportedException
   */
  public function configure($properties) {
    $section= $properties->getFirstSection();
    if ($section) do {
      if (false !== ($p= strpos($section, '.'))) {
        $this->queue($properties->readString($section, 'dsn'), substr($section, 0, $p), substr($section, $p+ 1));
      } else {
        $this->queue($properties->readString($section, 'dsn'), $section);
      }
      
    } while ($section= $properties->getNextSection());

    return true;
  }
  
  /**
   * Retrieves all registered connections as an array of DBConnection
   * objects.
   *
   * @return  rdbms.DBConnection[]
   */
  public function getConnections() {
    return array_values($this->pool);
  }
  
  /**
   * Register a connection
   *
   * @param   rdbms.DBConnection conn A connection object
   * @return  rdbms.DBConnection The connection object registered
   * @param   string hostAlias default NULL
   * @param   string userAlias default NULL
   */
  public function register(DBConnection $conn, $hostAlias= null, $userAlias= null) {
    $host= (null == $hostAlias) ? $conn->dsn->getHost() : $hostAlias;
    $user= (null == $userAlias) ? $conn->dsn->getUser() : $userAlias;
    
    $this->pool[$user.'@'.$host]= $conn;
    return $conn;
  }
  
  /**
   * Queue a connection string for registering on demand.
   *
   * @param   string dsn The connection's DSN
   * @return  rdbms.DSN
   * @param   string hostAlias default NULL
   * @param   string userAlias default NULL
   */
  public function queue($str, $hostAlias= null, $userAlias= null) {
    $dsn= new DSN($str);
    $host= (null == $hostAlias) ? $dsn->getHost() : $hostAlias;
    $user= (null == $userAlias) ? $dsn->getUser() : $userAlias;
    
    $this->pool[$user.'@'.$host]= $str;
    return $dsn;
  }
  
  /**
   * Return a database connection object by host and user
   *
   * @param   string host
   * @param   string user
   * @return  rdbms.DBConnection
   * @throws  rdbms.ConnectionNotRegisteredException in case there's no connection for these names
   */
  public function get($host, $user) {
    if (!isset($this->pool[$user.'@'.$host])) {
      throw new ConnectionNotRegisteredException(
        'No connections registered for '.$user.'@'.$host
      );
    }
    
    return $this->conn($user.'@'.$host, $this->pool[$user.'@'.$host]);
  }
  
  /**
   * Return one or more connections by host
   *
   * @param   string hostName
   * @param   int num default -1 offset, -1 for all
   * @return  var
   * @throws  rdbms.ConnectionNotRegisteredException in case there's no connection for these names
   */
  public function getByHost($hostName, $num= -1) {
    $results= [];
    foreach ($this->pool as $id => $value) {
      list ($user, $host)= explode('@', $id);
      if ($hostName == $host) $results[]= $this->conn($id, $value);
    }
    
    if (sizeof($results) < 1) {
      throw new ConnectionNotRegisteredException(
        'No connections registered for '.$hostName
      );
    }
    
    if ($num < 0) {
      return $results;
    }

    return $results[$num];
  }
  
  /**
   * Replace registered DSN with DBConnection if needed
   *
   * @param   string name name of connection
   * @param   var value either DSN or DBConnection
   * @return  rdbms.DBConnection
   */
  protected function conn($name, $value) {
    if ($value instanceof DBConnection) {
      return $value;
    } else if (is_string($value)) {         // Resolve lazy-loading DSNs
      return $this->pool[$name]= DriverManager::getConnection($value, false);
    }
    
    throw new DriverNotSupportedException('Neither a connection string nor a rdbms.DBConnection given.');
  }
}
