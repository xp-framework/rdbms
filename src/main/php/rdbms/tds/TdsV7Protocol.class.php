<?php namespace rdbms\tds;

/**
 * TDS V7 protocol implementation
 *
 * @see   https://github.com/mono/mono/blob/master/mcs/class/Mono.Data.Tds/Mono.Data.Tds.Protocol/Tds70.cs
 */
class TdsV7Protocol extends TdsProtocol {
  protected $servercs= 'cp1252';

  static function __static() { }

  /**
   * Setup record handlers
   *
   * @return  [:rdbms.tds.TdsRecord] handlers
   */
  protected function setupRecords() {
    $records[self::T_NUMERIC]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        $len= ($field['len'] ?? $stream->getByte()) - 1;
        if (-1 === $len) return null;
        $pos= $stream->getByte();
        for ($j= 0, $n= 0, $m= $pos ? 1 : -1; $j < $len; $j+= 4, $m= bcmul($m, '4294967296', 0)) {
          $n= bcadd($n, bcmul(sprintf('%u', $stream->getLong()), $m, 0), 0);
        }
        return $this->toNumber($n, $field['scale'], $field['prec']);
      }
    };
    $records[self::T_DECIMAL]= $records[self::T_NUMERIC];
    $records[self::T_VARIANT]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        if (0 === ($len= $stream->getLong())) return null;

        $base= $stream->getByte();
        $prop= $stream->getByte();
        if (isset($records[$base])) {
          $field['len']= $len- 2;      // Set length minus the the two bytes already read so it is not read twice

          // Special case handling - read more info. See query() method.
          // No need to handle text, ntext, image, timestamp, and sql_variant
          if (TdsProtocol::T_NUMERIC === $base || TdsProtocol::T_DECIMAL === $base) {
            $field['prec']= $stream->getByte();
            $field['scale']= $stream->getByte();
            $field['len']-= 2;
          } else if ($base > 128) {
            $stream->read(5);
          }

          return $records[$base]->unmarshal($stream, $field, $records);
        } else {
          throw new ProtocolException('Unknown variant base type 0x'.dechex($base));
        }
      }
    };
    $records[self::T_UNIQUE]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        $len= $field['len'] ?? $stream->getByte();
        if (0 === $len) return null;

        $bytes= $stream->read($len);
        return sprintf(
          '%02X%02X%02X%02X-%02X%02X-%02X%02X-%02X%02X-%02X%02X%02X%02X%02X%02X',
          ord($bytes[3]), ord($bytes[2]), ord($bytes[1]), ord($bytes[0]),
          ord($bytes[5]), ord($bytes[4]),
          ord($bytes[7]), ord($bytes[6]),
          ord($bytes[8]), ord($bytes[9]),
          ord($bytes[10]), ord($bytes[11]), ord($bytes[12]), ord($bytes[13]), ord($bytes[14]), ord($bytes[15])
        );
      }
    };
    $records[self::T_IMAGE]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        $has= $stream->getByte();
        if ($has !== 16) return null;

        $stream->read(24);  // Skip 16 Byte TEXTPTR, 8 Byte TIMESTAMP
        $len= $stream->getLong();
        if (0 === $len) {
          return null;
        } else if (\xp::ENCODING === $field['conv']) {
          return $stream->read($len);
        } else {
          return iconv($field['conv'], \xp::ENCODING, $stream->read($len));
        }
      }
    };
    $records[self::XT_BINARY]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        if (0xFFFF === ($len= $stream->getShort())) return null;
        $string= $stream->read($len);
        if (\xp::ENCODING === $field['conv']) {
          return substr($string, 0, strcspn($string, "\0"));
        } else {
          return iconv($field['conv'], \xp::ENCODING, substr($string, 0, strcspn($string, "\0")));
        }
      }
    };
    $records[self::XT_VARBINARY]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        if (0xFFFF === ($len= $stream->getShort())) return null;
        if (0 === $len) {
          return null;
        } else if (\xp::ENCODING === $field['conv']) {
          return $stream->read($len);
        } else {
          return iconv($field['conv'], \xp::ENCODING, $stream->read($len));
        }
      }
    };
    $records[self::XT_NVARCHAR]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        $len= $stream->getShort();
        return 0xFFFF === $len ? null : iconv('ucs-2le', \xp::ENCODING, $stream->read($len));
      }
    };
    self::$recordsFor[0][self::T_DATETIMN]= new class() extends TdsRecord {
      public function unmarshal($stream, $field, $records) {
        $len= $field['len'] ?? $stream->getByte();
        switch ($len) {
          case 4: return $this->toDate($stream->getShort(), $stream->getShort() * 60); break;
          case 8: return $this->toDate($stream->getLong(), $stream->getLong() / 300); break;
          default: return null;
        }
      }
    };
    return $records;
  }

  /**
   * Scrambles password
   *
   * @param   string password
   * @return  string
   */
  protected function scramble($password) {
    $xor= 0x5A5A;
    $s= '';
    for ($i= 0, $l= strlen($password); $i < $l; $i++) {
      $c= ord($password[$i]) ^ $xor;
      $s.= chr((($c >> 4) & 0x0F0F) | (($c << 4) & 0xF0F0))."\xA5";
    }
    return $s;
  }

  /**
   * Returns default packet size to use
   *
   * @return  int
   */
  protected function defaultPacketSize() { return 4096; }

  /**
   * Connect
   *
   * @param   string user
   * @param   string password
   * @param   string charset
   * @throws  io.IOException
   */
  protected function login($user, $password, $charset= null) {
    $params= [
      'hostname'   => [true, 'localhost'],
      'username'   => [true, $user],
      'password'   => [false, $this->scramble($password), strlen($password)],
      'appname'    => [true, 'XP-Framework'],
      'servername' => [true, 'localhost'],
      'unused'     => [false, '', 0],
      'library'    => [true, nameof($this)],
      'language'   => [true, 'us_english'],
      'database'   => [true, 'master']
    ];
    
    // Initial packet
    $login= pack(
      'VVVVVCCCCVV',
      0x71000001,         //  4: TDSVersion 7.1
      0,                  //  8: PacketSize
      7,                  // 12: ClientProgVer
      getmypid(),         // 16: ClientPID
      0,                  // 20: ConnectionID
      0x20 | 0x40 | 0x80, // 24: OptionFlags1 (use warning, initial db change, set language)
      0x03,               // 25: OptionFlags2
      0,                  // 26: TypeFlags
      0,                  // 27: (FRESERVEDBYTE / OptionFlags3)
      0,                  // 28: ClientTimZone
      0                   // 32: ClientLCID
    );

    // Offsets
    $offset= 86;
    $data= '';
    foreach ($params as $name => $param) {
      if ($param[0]) {
        $chunk= iconv(\xp::ENCODING, 'ucs-2le', $param[1]);
        $length= strlen($param[1]);
      } else {
        $chunk= $param[1];
        $length= $param[2];
      }
      $login.= pack('vv', $offset, $length);
      $offset+= strlen($chunk);
      $data.= $chunk;
    }
    
    // Packet end
    $login.= "\x00\x00\x00\x00\x00\x00";  // ClientID
    $login.= pack(
      'vvvv',
      $offset, 0,         // SSPI
      $offset, 0          // SSPILong
    );

    // Login
    $this->stream->write(self::MSG_LOGIN7, pack('V', $offset).$login.$data);
  }
  
  /**
   * Issues a query and returns the results
   *
   * @param   string sql
   * @return  var
   */
  public function query($sql) {
    $this->messages= [];
    $this->stream->write(self::MSG_QUERY, iconv(\xp::ENCODING, 'ucs-2le', $sql));
    $token= $this->read();

    do {
      if ("\x81" === $token) {          // COLMETADATA
        $fields= [];
        $nfields= $this->stream->getShort();
        for ($i= 0; $i < $nfields; $i++) {
          $field= $this->stream->get('Cx1/Cx2/Cflags/Cstatus/Ctype', 5);
          $field['conv']= $this->servercs;

          // Handle column.
          if (self::T_TEXT === $field['type'] || self::T_NTEXT === $field['type']) {
            $field['size']= $this->stream->getLong();
            $this->stream->read(5);     // XXX Collation?
            $field['table']= $this->stream->read($this->stream->getShort());
          } else if (self::T_NUMERIC === $field['type'] || self::T_DECIMAL === $field['type']) {
            $field['size']= $this->stream->getByte();
            $field['prec']= $this->stream->getByte();
            $field['scale']= $this->stream->getByte();
          } else if (self::XT_BINARY === $field['type'] || self::XT_VARBINARY === $field['type']) {
            $field['size']= $this->stream->getShort();
          } else if ($field['type'] > 128) {
            $field['size']= $this->stream->getShort();
            $this->stream->read(5);     // XXX Collation?
          } else if (isset(self::$fixed[$field['type']])) {
            $field['size']= self::$fixed[$field['type']];
          } else if (self::T_VARIANT === $field['type']) {
            $field['variant']= new \util\Bytes($this->stream->read(4));   // XXX Always {I\037\000\000}?
          } else if (self::T_IMAGE === $field['type']) {
            $field['size']= $this->stream->getLong();
            $field['table']= $this->stream->read($this->stream->getShort());
          } else {
            $field['size']= $this->stream->getByte();
          }

          $field['name']= $this->stream->getString($this->stream->getByte());
          $fields[]= $field;
        }
        return $fields;
      } else if ("\xFD" === $token || "\xFF" === $token || "\xFE" === $token) {   // DONE
        if (-1 === ($rows= $this->handleDone())) {
          $token= $this->stream->getToken();
          continue;
        }
        $this->done= true;
        return $rows;
      } else if ("\xAB" === $token) {   // INFO
        $this->handleInfo();
        $token= $this->stream->getToken();
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