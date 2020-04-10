<?php namespace rdbms\sybase;

use rdbms\DBEvent;
use util\log\BoundLogObserver;

/**
 * Observer class to observe a SybaseConnections IO
 * optimizer plan
 *
 * @ext  sybase
 */
class SybaseShowplanObserver implements BoundLogObserver {
  private $cat;

  protected
    $messages     = [],
    $queries      = [];
  
  protected static
    $messagecodes = [];
  
  static function __static() {
    self::$messagecodes= array_merge(
      range(3612,3615),
      range(6201,6299),
      range(10201,10299),
      range(302,310)
    );
  }

  /**
   * Creates a new log observer with a given log category.
   *
   * @param  util.log.LogCategory $cat
   */
  public function __construct($cat) {
    $this->cat= $cat;
  }

  /**
   * Sybase message callback.
   *
   * @param   int msgnumber
   * @param   int severity
   * @param   int state
   * @param   int line
   * @param   string text
   * @return  bool handled
   */
  public function _msghandler($msgnumber, $severity, $state, $line, $text) {

    // Filter 'optimizer'-messages by their msgnumber
    if (in_array($msgnumber, self::$messagecodes)) {
      $this->messages[]= [
        'msgnumber' => $msgnumber,
        'text'      => rtrim($text)
      ];
    }
    
    // Indicate we did not process the message
    return false;
  }

  /**
   * Retrieves an instance.
   *
   * @param   var argument
   * @return  rdbms.sybase.SybaseShowplanObserver
   */
  public static function instanceFor($arg) {
    return new SybaseShowplanObserver($arg);
  }
  
  /**
   * Update the observer. Process new message.
   *
   * @param   var observable
   * @param   var dbevent
   */
  public function update($obs, $arg= null) {
    if ($arg instanceof DBEvent) {
      $event= 'on'.$arg->getName();
      method_exists($this, $event) && $this->{$event}($obs, $arg);
    }
  }
  
  /**
   * Process connect events.
   *
   * @param   var observable
   * @param   var dbevent
   */
  public function onConnect($obs, $arg) {
    ini_set('sybct.min_server_severity', 0);
    sybase_set_message_handler([$this, '_msghandler'], $obs->handle);
    sybase_query('set showplan on', $obs->handle);

    // Reset query- and message-cache
    $this->queries= $this->messages= [];
  }
  
  /**
   * Process query event.
   *
   * @param   var observable
   * @param   var dbevent
   */
  public function onQuery($obs, $arg) {
    
    // Add query to cache
    $this->queries[]= $arg->getArgument();
  }
  
  /**
   * Process end of query event.
   *
   * @param   var observable
   * @param   var dbevent
   */
  public function onQueryEnd($obs, $arg) {
    $this->cat->info(nameof($this).'::onQueryEnd() Query was:', (sizeof($this->queries) == 1 ? $this->queries[0] : $this->queries));

    $showplan= '';
    foreach (array_keys($this->messages) as $idx) {
      $showplan.= $this->messages[$idx]['text']."\n";
    }
    
    $this->cat->infof("Showplan output is:\n%s", $showplan);
    $this->queries= $this->messages= [];
  }
} 