<?php namespace rdbms\tds;

use peer\Socket;
use peer\ProtocolException;

/**
 * TDS protocol implementation
 *
 * @see   http://en.wikipedia.org/wiki/Tabular_Data_Stream
 * @see   http://msdn.microsoft.com/en-us/library/cc448435.aspx
 * @see   http://www.freetds.org/tds.html
 * @see   https://github.com/mono/mono/tree/master/mcs/class/Mono.Data.Tds/Mono.Data.Tds.Protocol
 */
abstract class TdsProtocol extends \lang\Object {
  protected $servercs= 'cp850';
  protected $stream= null;
  protected $done= false;
  protected $records= [];
  protected $messages= [];
  public $connected= false;

  // Record handler cache per base class implementation
  protected static $recordsFor= [];

  // Messages
  const MSG_QUERY    = 0x1;
  const MSG_LOGIN    = 0x2;
  const MSG_REPLY    = 0x4;
  const MSG_CANCEL   = 0x6;
  const MSG_LOGIN7   = 0x10;
  const MSG_LOGOFF   = 0x71;

  // Types
  const T_CHAR       = 0x2F;
  const T_VARCHAR    = 0x27;
  const T_INTN       = 0x26;
  const T_INT1       = 0x30;
  const T_DATE       = 0x31;
  const T_TIME       = 0x33;
  const T_INT2       = 0x34;
  const T_INT4       = 0x38;
  const T_INT8       = 0x7F;
  const T_FLT8       = 0x3E;
  const T_DATETIME   = 0x3D;
  const T_BIT        = 0x32;
  const T_TEXT       = 0x23;
  const T_NTEXT      = 0x63;
  const T_IMAGE      = 0x22;
  const T_MONEY4     = 0x7A;
  const T_MONEY      = 0x3C;
  const T_DATETIME4  = 0x3A;
  const T_REAL       = 0x3B;
  const T_BINARY     = 0x2D;
  const T_VOID       = 0x1F;
  const T_VARBINARY  = 0x25;
  const T_NVARCHAR   = 0x67;
  const T_BITN       = 0x68;
  const T_NUMERIC    = 0x6C;
  const T_DECIMAL    = 0x6A;
  const T_FLTN       = 0x6D;
  const T_MONEYN     = 0x6E;
  const T_DATETIMN   = 0x6F;
  const T_DATEN      = 0x7B;
  const T_TIMEN      = 0x93;
  const XT_CHAR      = 0xAF;
  const XT_VARCHAR   = 0xA7;
  const XT_NVARCHAR  = 0xE7;
  const XT_NCHAR     = 0xEF;
  const XT_VARBINARY = 0xA5;
  const XT_BINARY    = 0xAD;
  const T_UNITEXT    = 0xAE;
  const T_LONGBINARY = 0xE1;
  const T_SINT1      = 0x40;
  const T_UINT2      = 0x41;
  const T_UINT4      = 0x42;
  const T_UINT8      = 0x43;
  const T_UINTN      = 0x44;
  const T_UNIQUE     = 0x24;
  const T_VARIANT    = 0x62;
  const T_SINT8      = 0xBF;

  protected static $fixed= [
    self::T_INT1      => 1,
    self::T_INT2      => 2,
    self::T_INT4      => 4,
    self::T_INT8      => 8,
    self::T_FLT8      => 8,
    self::T_BIT       => 1,
    self::T_MONEY4    => 4,
    self::T_MONEY     => 8,
    self::T_REAL      => 4,
    self::T_DATE      => 4,
    self::T_TIME      => 4,
    self::T_DATETIME4 => 4,
    self::T_DATETIME  => 8,
    self::T_SINT1     => 1,
    self::T_UINT2     => 2,
    self::T_UINT4     => 3,
    self::T_UINT8     => 8,
    self::T_SINT8     => 8,
  ];

  static function __static() {
    self::$recordsFor[0][self::T_VARCHAR]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= $stream->getByte();
        if (0 === $len) {
          return null;
        } else if (\xp::ENCODING === $field["conv"]) {
          return $stream->read($len);
        } else {
          return iconv($field["conv"], \xp::ENCODING, $stream->read($len));
        }
      }
    ]);
    self::$recordsFor[0][self::XT_VARCHAR]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= $stream->getShort();
        if (0xFFFF === $len) {
          return null;
        } else if (\xp::ENCODING === $field["conv"]) {
          return $stream->read($len);
        } else {
          return iconv($field["conv"], \xp::ENCODING, $stream->read($len));
        }
      }
    ]);
    self::$recordsFor[0][self::XT_NVARCHAR]= self::$recordsFor[0][self::XT_VARCHAR];
    self::$recordsFor[0][self::T_INTN]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= isset($field["len"]) ? $field["len"] : $stream->getByte();
        switch ($len) {
          case 1: return $stream->getByte();
          case 2: return $stream->getShort();
          case 4: return $stream->getLong();
          case 8: return $this->toNumber($stream->getInt64(), 0, 0);
          default: return null;
        }
      }
    ]);
    self::$recordsFor[0][self::T_INT1]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getByte();
      }
    ]);
    self::$recordsFor[0][self::T_INT2]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getShort();
      }
    ]);
    self::$recordsFor[0][self::T_INT4]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getLong();
      }
    ]);
    self::$recordsFor[0][self::T_INT8]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toNumber($stream->getInt64(), 0, 0);
      }
    ]);
    self::$recordsFor[0][self::T_SINT1]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getByte();
      }
    ]);
    self::$recordsFor[0][self::T_UINT2]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getShort();
      }
    ]);
    self::$recordsFor[0][self::T_UINT4]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getLong();
      }
    ]);
    self::$recordsFor[0][self::T_UINT8]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toNumber($stream->getUInt64(), 0, 0);
      }
    ]);
    self::$recordsFor[0][self::T_UINTN]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= isset($field["len"]) ? $field["len"] : $stream->getByte();
        switch ($len) {
          case 2: return $stream->getShort();
          case 4: return $stream->getLong();
          case 8: $this->toNumber($stream->getUInt64(), 0, 0);
          default: return null;
        }
      }
    ]);
    self::$recordsFor[0][self::T_FLTN]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= isset($field["len"]) ? $field["len"] : $stream->getByte();
        switch ($len) {
          case 4: return $this->toFloat($stream->read(4)); break;
          case 8: return $this->toDouble($stream->read(8)); break;
          default: return null;
        }
      }
    ]);
    self::$recordsFor[0][self::T_FLT8]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toDouble($stream->read(8));
      }
    ]);
    self::$recordsFor[0][self::T_REAL]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toFloat($stream->read(4));
      }
    ]);
    self::$recordsFor[0][self::T_DATE]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toDate($stream->getLong(), 0);
      }
    ]);
    self::$recordsFor[0][self::T_DATETIME]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toDate($stream->getLong(), $stream->getLong());
      }
    ]);
    self::$recordsFor[0][self::T_DATETIME4]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toDate($stream->getShort(), $stream->getShort() * 60);
      }
    ]);
    self::$recordsFor[0][self::T_DATETIMN]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= isset($field["len"]) ? $field["len"] : $stream->getByte();
        switch ($len) {
          case 4: return $this->toDate($stream->getShort(), $stream->getShort() * 60); break;
          case 8: return $this->toDate($stream->getLong(), $stream->getLong()); break;
          default: return null;
        }
      }
    ]);
    self::$recordsFor[0][self::T_MONEYN]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $len= isset($field["len"]) ? $field["len"] : $stream->getByte();
        switch ($len) {
          case 4: return $this->toMoney($stream->getLong()); break;
          case 8: return $this->toMoney($stream->getLong(), $stream->getLong()); break;
          default: return null;
        }
      }
    ]);
    self::$recordsFor[0][self::T_MONEY4]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toMoney($stream->getLong());
      }
    ]);
    self::$recordsFor[0][self::T_MONEY]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $this->toMoney($stream->getLong(), $stream->getLong());
      }
    ]);
    self::$recordsFor[0][self::T_CHAR]= self::$recordsFor[0][self::T_VARCHAR];
    self::$recordsFor[0][self::XT_CHAR]= self::$recordsFor[0][self::XT_VARCHAR];
    self::$recordsFor[0][self::T_TEXT]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $has= $stream->getByte();
        if ($has !== 16) return null;

        $stream->read(24);  // Skip 16 Byte TEXTPTR, 8 Byte TIMESTAMP

        $len= $stream->getLong();
        if ($len === 0) {
          return $field["status"] & 0x20 ? null : "";
        } else if (\xp::ENCODING === $field["conv"]) {
          return $stream->read($len);
        } else {
          return iconv($field["conv"], \xp::ENCODING, $stream->read($len));
        }
      }
    ]);
    self::$recordsFor[0][self::T_NTEXT]= self::$recordsFor[0][self::T_TEXT];
    self::$recordsFor[0][self::T_BITN]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getByte() ? $stream->getByte() : null;
      }
    ]);
    self::$recordsFor[0][self::T_BIT]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        return $stream->getByte();
      }
    ]);
    self::$recordsFor[0][self::T_UNITEXT]= newinstance('rdbms.tds.TdsRecord', [], [
      'unmarshal' => function ($stream, $field, $records) {
        $ptr= $stream->getByte();
        $stream->read($ptr + 8);    // Skip TEXTPTR + 8 Bytes TIMESTAMP

        $len= $stream->getLong();
        if ($len === 0) {
          return $field["status"] & 0x20 ? null : "";
        } else {
          $chunk= $stream->read($len);
          return iconv("utf-16le", \xp::ENCODING, $chunk);
        }
      }
    ]);
  }

  /**
   * Creates a new protocol instance
   *
   * @param   peer.Socket s
   */
  public function __construct(Socket $s) {
    $this->stream= new TdsDataStream($s, $this->defaultPacketSize());

    // Cache record handlers per instance
    $impl= nameof($this);
    if (!isset(self::$recordsFor[$impl])) {
      self::$recordsFor[$impl]= $this->setupRecords() + self::$recordsFor[0];
    }
    $this->records= self::$recordsFor[$impl];
  }

  /**
   * Setup record handlers
   *
   * @return  [:rdbms.tds.TdsRecord] handlers
   */
  protected abstract function setupRecords();

  /**
   * Returns default packet size to use
   *
   * @return  int
   */
  protected abstract function defaultPacketSize();
  
  /**
   * Send login record
   *
   * @param   string user
   * @param   string password
   * @throws  io.IOException
   */
  protected abstract function login($user, $password);

   /**
   * Handle messages
   *
   * @param   string message
   * @param   int number
   * @param   int state
   * @param   int class
   * @param   string server
   * @param   string proc
   * @param   int line
   */
  protected function handleMessage($message, $number= 0, $state= 0, $class= 0, $server= null, $proc= null, $line= 0) {
    $this->messages[]= [
      'message' => trim($message),
      'number'  => $number,
      'state'   => $state,
      'class'   => $class,
      'server'  => $server,
      'proc'    => $proc,
      'line'    => $line
    ];
  }

  /**
   * Returns an exception consuming all server messages into causes
   *
   * @param  string prefix
   * @return rdbms.tds.TdsProtocolException
   */
  protected function exception($prefix= null) {
    if ($this->messages) {
      $cause= null;
      while ($e= array_shift($this->messages)) {
        $cause= new TdsProtocolException(
          $e['message'],
          $e['number'],
          $e['state'],
          $e['class'],
          $e['server'],
          $e['proc'],
          $e['line'],
          $cause
        );
      }
    } else {
      $cause= new TdsProtocolException('Unexpected protocol error', -1, -1, 0xFF, null, null, -1);
    }

    if (null !== $prefix) {
      $cause->message= $prefix.': '.$cause->message;
    }
    return $cause;
  }


  /**
   * Handles ERROR messages (0xAA)
   *
   * @throws  rdbms.tds.TdsProtocolException
   */
  protected function handleError() {
    $meta= $this->stream->get('vlength/Vnumber/Cstate/Cclass', 8);
    $message= $this->stream->getString($this->stream->getShort());
    $server= $this->stream->getString($this->stream->getByte());
    $proc= $this->stream->getString($this->stream->getByte());
    $line= $this->stream->getByte();

    $this->handleMessage($message, $meta['number'], $meta['state'], $meta['class'], $server, $proc, $line);
  }

  /**
   * Handles INFO messages (0xAB)
   *
   * @throws  rdbms.tds.TdsProtocolException
   */
  protected function handleInfo() {
    $meta= $this->stream->get('vlength/Vnumber/Cstate/Cclass', 8);
    $message= $this->stream->getString($this->stream->getShort());
    $server= $this->stream->getString($this->stream->getByte());
    $proc= $this->stream->getString($this->stream->getByte());
    $line= $this->stream->getShort();

    $this->handleMessage($message, $meta['number'], $meta['state'], $meta['class'], $server, $proc, $line);
  }

  /**
   * Handles EED text messages (0xE5)
   *
   * @throws  rdbms.tds.TdsProtocolException
   */
  protected function handleEED() {
    $meta= $this->stream->get('vlength/Vnumber/Cstate/Cclass', 8);
    $meta['sqlstate']= $this->stream->read($this->stream->getByte());
    $meta= array_merge($meta, $this->stream->get('Cstatus/vtranstate', 3));
    $message= $this->stream->read($this->stream->getShort());
    $server= $this->stream->read($this->stream->getByte());
    $proc= $this->stream->read($this->stream->getByte());
    $line= $this->stream->getShort();

    $this->handleMessage($message, $meta['number'], $meta['state'], $meta['class'], $server, $proc, $line);
  }

  /**
   * Handles DONE tokens (0xFD, 0xFF, 0xFE)
   *
   * @throws  rdbms.tds.TdsProtocolException
   * @return  int rowcount, or -1 to indicate more results
   */
  protected function handleDone() {
    $meta= $this->stream->get('vstatus/vtranstate/Vrowcount', 8);
    if ($meta['status'] & 0x0001) {         // TDS_DONE_MORE
      return -1;
    } else if ($meta['status'] & 0x0002) {  // TDS_DONE_ERROR
      throw $this->exception();
    } else if (!empty($this->messages) && (3 === $meta['transtate']  || 4 === $meta['transtate'])) {
      throw $this->exception('Aborted');
    }
    return $meta['rowcount'];
  }

  /**
   * Handle ENVCHANGE
   *
   * @param  int type
   * @param  string old
   * @param  string new
   * @param  bool initial if this ENVCHANGE was part of the login response
   */
  protected function handleEnvChange($type, $old, $new, $initial= false) {
    if ($initial && 3 === $type) {
      $this->servercs= strtr($new, ['iso_' => 'iso-8859-', 'utf8' => 'utf-8']);
    }
    // DEBUG Console::writeLine($initial ? 'I' : 'E', $type, ' ', $old, ' -> ', $new);
  }

  /**
   * Connect
   *
   * @param   string user
   * @param   string password
   * @param   string charset
   * @throws  io.IOException
   */
  public function connect($user= '', $password= '', $charset= null) {
    $this->connected= false;
    $this->stream->connect();
    $this->messages= [];
    $this->login($user, $password, $charset);
    $token= $this->read();

    do {
      if ("\xAD" === $token) {          // TDS_LOGINACK
        $meta= $this->stream->get('vlength/Cstatus', 3);
        $this->stream->read($meta['length']- 1);
        if (7 === $meta['status']) {
          $this->cancel();
          throw $this->exception('Negotiation not yet implemented');
        }
      } else if ("\xE2" === $token) {   // TDS_CAPABILITY
        $meta= $this->stream->get('nlength', 2);
        $this->stream->read($meta['length']);
      } else if ("\xE3" === $token) {   // TDS_ENVCHANGE
        $this->envchange();
      } else if ("\xAB" === $token) {
        $this->handleInfo();
      } else if ("\xE5" === $token) {
        $this->handleEED();
      } else if ("\xFD" === $token) {
        $this->handleDone();            // Throws on error
        $this->connected= true;
        return;
      } else {
        $this->cancel();
        throw $this->exception('Unexpected login response '.dechex(ord($token)));
      }
    } while ($token= $this->stream->getToken());

    throw $this->exception('Unexpected login handshake error');
  }

  /**
   * Process an ENVCHANGE token, e.g. "\015\003\005iso_1\005cp850"
   *
   * @return void
   */
  protected function envchange() {
    $len= $this->stream->getShort();
    $env= $this->stream->read($len);
    $i= 0;
    while ($i < $len) {
      $type= $env{$i++};
      $new= substr($env, $i+ 1, $l= ord($env{$i++}));
      $i+= $l;
      $old= substr($env, $i+ 1, $l= ord($env{$i++}));
      $i+= $l;
      $this->handleEnvChange(ord($type), $old, $new, true);
    }
  }
  
  /**
   * Protocol read
   *
   * @return  string the message token
   * @throws  peer.ProtocolException
   */
  protected function read() {
    $this->done= false;
    $type= $this->stream->begin();

    // Check for message type
    if (self::MSG_REPLY !== $type) {
      $this->cancel();
      throw new ProtocolException('Unknown message type '.$type);
    }

    // Handle errors - see also 2.2.5.7: Data Buffer Stream Tokens
    $token= $this->stream->getToken();
    if ("\xAA" === $token) {
      $this->handleError();
      $this->done= true;
      throw $this->exception();
    }

    return $token;
  }

  /**
   * Check whether connection is ready
   *
   * @return  bool
   */
  public function ready() {
    return $this->done;
  }

  /**
   * Cancel result set
   *
   */
  public function cancel() {
    if (!$this->done) {
      $this->stream->read(-1);    // TODO: Send cancel, then read rest. Will work like this, though, too.
      $this->done= true;
    }
  }

  /**
   * Execute SQL in "fire and forget" mode.
   *
   * @param   string sql
   */
  public function exec($sql) {
    if (is_array($r= $this->query($sql))) {
      $this->cancel();
    }
  }

  /**
   * Issues a query and returns the results
   *
   * @param   string sql
   * @return  var
   */
  public abstract function query($sql);

  /**
   * Fetches one record
   *
   * @param   [:var][] fields
   * @return  [:var] record
   */
  public function fetch($fields) {
    $token= $this->stream->getToken();
    do {
      if ("\xAE" === $token) {              // TDS_CONTROL
        $length= $this->stream->getShort();
        for ($i= 0; $i < $length; $i++) {
          $this->stream->read($this->stream->getByte());
        }
        $token= $this->stream->getToken();
        $continue= true;
      } else if ("\xAA" === $token) {
        $this->handleError();
        throw $this->exception();
      } else if ("\xE5" === $token) {
        $this->handleEED();
        $token= $this->stream->getToken();
        $continue= true;
      } else if ("\xA9" === $token) {       // TDS_COLUMNORDER
        $this->stream->read($this->stream->getShort());
        $token= $this->stream->getToken();
        $continue= true;
      } else if ("\xFD" === $token || "\xFF" === $token || "\xFE" === $token) {   // DONE
        if (-1 === ($rows= $this->handleDone())) {
          $token= $this->stream->getToken();
          $continue= true;
        } else {
          $this->done= true;
          return null;
        }
      } else if ("\xD1" !== $token) {
        // Console::$err->writeLinef('END TOKEN %02x', ord($token));    // 2.2.5.7 Data Buffer Stream Tokens
        $this->done= true;
        return null;
      } else {
        $continue= false;
      }
    } while ($continue);
    
    $record= [];
    foreach ($fields as $i => $field) {
      $type= $field['type'];
      if (!isset($this->records[$type])) {
        \util\cmd\Console::$err->writeLinef('Unknown field type 0x%02x', $type);
        continue;
      }
      $record[$i]= $this->records[$type]->unmarshal($this->stream, $field, $this->records);
    }
    return $record;
  }

  /**
   * Close
   *
   */
  public function close() {
    if (!$this->connected) return;

    try {
      $this->stream->write(self::MSG_LOGOFF, "\0");
    } catch (\io\IOException $ignored) {
      // Can't do much here
    } 

    $this->stream->close();
    $this->connected= false;
  }
  
  /**
   * Creates a string representation
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'('.\xp::stringOf($this->stream).')';
  }
}
