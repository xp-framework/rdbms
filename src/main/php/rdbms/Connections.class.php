<?php namespace rdbms;

class Connections {
  private $automatic, $attempts;

  /**
   * Creates a new instance
   *
   * @param  bool $automatic Autoconnect semantics, defaults to TRUE
   * @param  int $attempts Reconnect attempts when disconnected, defaults to 1
   */
  public function __construct($automatic= true, $attempts= 1) {
    $this->automatic= $automatic;
    $this->attempts= $attempts;
  }

  /**
   * Set whether to use autoconnect semantics
   *
   * @param  bool $automatic
   * @return self
   */
  public function automatic($automatic) {
    $this->automatic= $automatic;
    return $this;
  }

  /**
   * Set how often to try reconnecting after server disconnected
   *
   * @param  int $attempts
   * @return self
   */
  public function reconnect($attempts) {
    $this->attempts= $attempts;
    return $this;
  }

  /**
   * Establish connection if necessary
   *
   * @param  rdbms.DBConnection $conn
   * @return void
   */
  public function establish($conn) {
    if ($this->automatic) {
      if (false === $conn->connect()) {
        throw new SQLStateException('Previously failed to connect.');
      }
    } else {
      throw new SQLStateException('Not connected');
    }
  }
  
  /**
   * Handle situation when server disconnected
   *
   * @param  rdbms.DBConnection $conn
   * @param  int $tries
   * @return bool Whether to retry
   */
  public function retry($conn, $tries) {
    if ($tries > $this->attempts) return false;

    $conn->close();
    $conn->connect();
    return true;
  }
}