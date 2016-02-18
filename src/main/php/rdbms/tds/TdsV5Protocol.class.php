<?php namespace rdbms\tds;

/**
 * TDS V5 protocol implementation
 *
 * @see   http://www.sybase.com/content/1013412/tds34.pdf
 * @see   https://github.com/mono/mono/blob/master/mcs/class/Mono.Data.Tds/Mono.Data.Tds.Protocol/Tds50.cs
 */
class TdsV5Protocol extends TdsProtocol {
  protected $servercs= 'cp850';

  static function __static() { }

  /**
   * Setup record handlers
   *
   * @see     http://infocenter.sybase.com/help/topic/com.sybase.dc35823_1500/html/uconfig/uconfig111.htm
   * @see     http://infocenter.sybase.com/help/topic/com.sybase.dc38421_1500/html/ntconfig/ntconfig80.htm
   * @return  [:rdbms.tds.TdsRecord] handlers
   */
  protected function setupRecords() {
    $records[self::T_NUMERIC]= newinstance('rdbms.tds.TdsRecord', [], '{
      public function unmarshal($stream, $field, $records) {
        if (-1 === ($len= $stream->getByte()- 1)) return null;
        $pos= $stream->getByte();
        $bytes= $stream->read($len);
        if ($i= ($len % 4)) {
          $bytes= str_repeat("\0", 4 - $i).$bytes;
          $len+= 4 - $i;
        }
        for ($n= 0, $m= $pos ? -1 : 1, $i= $len- 4; $i >= 0; $i-= 4, $m= bcmul($m, "4294967296", 0)) {
          $n= bcadd($n, bcmul(sprintf("%u", current(unpack("N", substr($bytes, $i, 4)))), $m, 0), 0);
        }
        return $this->toNumber($n, $field["scale"], $field["prec"]);
      }
    }');
    $records[self::T_DECIMAL]= $records[self::T_NUMERIC];
    $records[self::T_BINARY]= newinstance('rdbms.tds.TdsRecord', [], '{
      public function unmarshal($stream, $field, $records) {
        if (0 === ($len= $stream->getByte())) return null;
        $string= $stream->read($len);
        return iconv($field["conv"], \xp::ENCODING, substr($string, 0, strcspn($string, "\0")));
      }
    }');
    $records[self::T_IMAGE]= newinstance('rdbms.tds.TdsRecord', [], '{
      public function unmarshal($stream, $field, $records) {
        $has= $stream->getByte();
        if ($has !== 16) return null; // Seems to always be 16 - obsolete?

        $stream->read(24);  // Skip 16 Byte TEXTPTR, 8 Byte TIMESTAMP
        $len= $stream->getLong();
        if (0 === $len) return null;

        $r= $stream->read($len);

        // HACK - cannot figure out why UNITEXT is not being returned as such
        // but as IMAGE type with different inside layout!
        return iconv(
          strlen($r) > 1 && "\0" === $r{1} ? "ucs-2le" : $field["conv"],
          \xp::ENCODING,
          $r
        );
      }
    }');
    $records[self::T_VARBINARY]= newinstance('rdbms.tds.TdsRecord', [], '{
      public function unmarshal($stream, $field, $records) {
        if (0 === ($len= $stream->getByte())) return null;

        return iconv($field["conv"], \xp::ENCODING, $stream->read($len));
      }
    }');
    $records[self::T_LONGBINARY]= newinstance('rdbms.tds.TdsRecord', [], '{
      public function unmarshal($stream, $field, $records) {
        $len= $stream->getLong();
        return $stream->getString($len / 2);
      }
    }');
    return $records;
  }

  /**
   * Returns default packet size to use
   *
   * @return  int
   */
  protected function defaultPacketSize() {
    return 512;
  }

  /**
   * Connect
   *
   * @param   string user
   * @param   string password
   * @param   string charset
   * @throws  io.IOException
   */
  protected function login($user, $password, $charset= null) {
    if (strlen($password) > 253) {
      throw new \lang\IllegalArgumentException('Password length must not exceed 253 bytes.');
    }

    $charset= $charset ?: 'utf8';
    $packetSize= (string)$this->defaultPacketSize();
    $packet= pack(
      'a30Ca30Ca30Ca30CCCCCCCCCCx7a30Ca30CCCa253CCCCCa10CCCCCCCCa30CCnCx8na30CCa6Cx4',
      'localhost', min(30, strlen('localhost')),
      $user, min(30, strlen($user)),
      $password, min(30, strlen($password)),
      (string)getmypid(), min(30, strlen(getmypid())),
      0x03,       // Byte order for 2 byte ints: 2 = <MSB, LSB>, 3 = <LSB, MSB>
      0x01,       // Byte order for 4 byte ints: 0 = <MSB, LSB>, 1 = <LSB, MSB>
      0x06,       // Character rep (6 = ASCII, 7 = EBCDIC)
      0x0A,       // Eight byte floating point rep (10 =  IEEE <LSB, ..., MSB>)
      0x09,       // Eight byte date format (8 = <MSB, ..., LSB>)
      0x01,       // Notify of "use db"
      0x01,       // Disallow dump/load and bulk insert
      0x00,       // SQL Interface type
      0x00,       // Type of network connection
      nameof($this), min(30, strlen(nameof($this))),
      'localhost', min(30, strlen('localhost')),
      0, strlen($password),               // Remote passwords
      $password, strlen($password)+ 2,    // Long version of password
      0x05, 0x00, 0x00, 0x00,             // TDS Version
      'XP:rdbms', strlen('XP:rdbms'),     // Client library name
      0x06, 0x00, 0x00, 0x00,             // Prog version
      0x00,                               // Auto convert short
      0x0D,                               // Type of flt4
      0x11,                               // Type of date4
      'us_english', strlen('us_english'), // Language
      0x00,                               // Notify on lang change
      0x00,                               // Security label hierarchy
      0x00,                               // Security spare
      0x00,                               // Security login role
      $charset, strlen($charset),         // Charset
      0x01,                               // Notify on charset change
      $packetSize, strlen($packetSize)    // Network packet size (in text!)
    );

    // Request and response capabilities
    $capabilities= "\342\0\30\1\f\7\315\377\205\356\357e\177\377\377\377\326\2\10\0\6\200\6H\0\0\0";

    // Login
    $this->stream->write(self::MSG_LOGIN, $packet.$capabilities);
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
   * Reads data type descriptions for both TDS_ROWFMT and TDS_ROWFMT2
   *
   * @param  [:var] $field
   * @return [:var]
   */
  private function fieldOf($field) {
    $field['name']= $field['label'] ?: $field['column'];

    $this->stream->read(4);     // Skip usertype
    $field['type']= $this->stream->getByte();

    if (self::T_TEXT === $field['type'] || self::T_IMAGE === $field['type']) {
      $field['size']= $this->stream->getLong();
      $this->stream->read($this->stream->getShort());
    } else if (self::T_NUMERIC === $field['type'] || self::T_DECIMAL === $field['type']) {
      $field['size']= $this->stream->getByte();
      $field['prec']= $this->stream->getByte();
      $field['scale']= $this->stream->getByte();
    } else if (self::T_LONGBINARY === $field['type'] || self::XT_CHAR === $field['type']) {
      $field['size']= $this->stream->getLong() / 2;
    } else if (isset(self::$fixed[$field['type']])) {
      $field['size']= self::$fixed[$field['type']];
    } else if (self::T_UNITEXT === $field['type']) {
      $field['size']= $this->stream->getByte();
      $this->stream->read(5);       // XXX Collation?
    } else if (self::T_VARBINARY === $field['type'] || self::T_BINARY === $field['type']) {
      $field['size']= $this->stream->getByte();
    } else {
      $field['size']= $this->stream->getByte();
    }

    $field['locale']= $this->stream->getByte();
    return $field;
  }

  /**
   * Issues a query and returns the results
   *
   * @param   string sql
   * @return  var
   */
  public function query($sql) {
    $this->messages= [];
    if (\xp::ENCODING === $this->servercs) {
      $this->stream->write(self::MSG_QUERY, $sql);
    } else{
      $this->stream->write(self::MSG_QUERY, iconv(\xp::ENCODING, $this->servercs, $sql));
    }
    $token= $this->read();

    // Skip over DONEPROC & DONEINPROC results
    do {
      if ("\x00" === $token || "\x02" === $token) {

        // Tokens encountered in some situations, seem to be inserted after a certain number
        // of rows, we need to continue reading in these cases (if we don't, we experience
        // issues like https://github.com/xp-framework/xp-framework/issues/305). Examples:
        //
        // packet header           * token * data
        // ----------------------- * ----- * -----------
        // 04 01 00 0A 00 00 00 00 * 00 00 *
        // 04 01 00 0E 00 00 00 00 * 02 00 * 19 00 00 00
        $token= $this->read();
        continue;
      } else if ("\xA3" === $token || "\x23" === $token || "\x10" === $token) {
        $token= $this->read();                 // TDS_CURDECLARE*
        continue;
      } else if ("\x61" === $token) {          // TDS_ROWFMT2
        $this->stream->read(4);
        $nfields= $this->stream->getShort();
        $fields= [];
        for ($i= 0; $i < $nfields; $i++) {
          $fields[]= $this->fieldOf([
            'conv'    => $this->servercs,
            'label'   => $this->stream->read($this->stream->getByte()),
            'catalog' => $this->stream->read($this->stream->getByte()),
            'schema'  => $this->stream->read($this->stream->getByte()),
            'table'   => $this->stream->read($this->stream->getByte()),
            'column'  => $this->stream->read($this->stream->getByte()),
            'status'  => $this->stream->getLong()
          ]);
        }
        return $fields;
      } else if ("\xEE" === $token) {          // TDS_ROWFMT
        $this->stream->read(2);
        $nfields= $this->stream->getShort();
        $fields= [];
        for ($i= 0; $i < $nfields; $i++) {
          $fields[]= $this->fieldOf([
            'conv'    => $this->servercs,
            'label'   => null,
            'column'  => $this->stream->read($this->stream->getByte()),
            'status'  => $this->stream->getByte()
          ]);
        }
        return $fields;
      } else if ("\xFD" === $token || "\xFF" === $token || "\xFE" === $token) {   // DONE
        if (-1 === ($rows= $this->handleDone())) {
          $token= $this->stream->getToken();
          continue;
        }
        $this->done= true;
        return $rows;
      } else if ("\xE5" === $token) {   // EED (messages or errors)
        $this->handleEED();
        $token= $this->stream->getToken();
      } else if ("\xE3" === $token) {   // ENVCHANGE, e.g. from "use [db]" queries
        $this->envchange();
        return null;
      } else if ("\x79" === $token) {   // RETURN_STATUS (eg. from stored procedures), ignore for the moment
        $this->stream->getLong();
        $token= $this->stream->getToken();
      } else {
        throw new TdsProtocolException(
          sprintf('Unexpected token 0x%02X', ord($token)),
          0,    // Number
          0,    // State
          0,    // Class
          null, // Server
          null, // Proc
          -1    // Line
        );
      }
    } while (!$this->done);
  }
}
