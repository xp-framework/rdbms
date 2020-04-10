<?php namespace rdbms\tds;

/**
 * Indicate an error was detected in the protocol
 *
 * @see   xp://rdbms.tds.TdsProtocol
 */
class TdsProtocolException extends \peer\ProtocolException {
  public $number;
  public $state;
  public $class;
  public $server;
  public $proc;
  public $line;
  
  /**
   * Constructor
   *
   * @param   string $message
   * @param   int $number
   * @param   int $state
   * @param   int $class
   * @param   string $server
   * @param   string $proc
   * @param   int $line
   * @param   lang.Throwable $cause
   */
  public function __construct($message, $number= 0, $state= 0, $class= 0, $server= null, $proc= null, $line= 0, $cause= null) {
    parent::__construct($message, $cause);
    $this->number= $number;
    $this->state= $state;
    $this->class= $class;
    $this->server= $server;
    $this->proc= $proc;
    $this->line= $line;
  }

  /**
   * Return compound message of this exception.
   *
   * @return  string
   */
  public function compoundMessage() {
    $addr= array_filter([$this->server, $this->proc, $this->line]);
    return sprintf(
      'Exception %s (#%d, state %d, class %d: %s%s)',
      nameof($this),
      $this->number,
      $this->state,
      $this->class,
      $this->message,
      $addr ? ' @ '.implode(':', $addr) : ''
    );
  }
}