<?php namespace rdbms\unittest\tds;

use lang\{ClassLoader, IllegalArgumentException};
use peer\Socket;
use rdbms\tds\{TdsDataStream, TdsProtocolException};
use test\{Assert, Before, Expect, Test};
use util\Bytes;

class TdsDataStreamTest {
  protected static $sock;

  #[Before]
  public function mockSocket() {
    self::$sock= ClassLoader::defineClass('rdbms.unittest.tds.MockTdsSocket', Socket::class, [], '{
      public $bytes;
      protected $offset= 0;
      
      public function __construct($bytes= "") {
        $this->bytes= (string)$bytes;
      }

      public function write($bytes) {
        $this->bytes.= $bytes;
      }
      
      public function readBinary($maxLen= 4096) {
        $chunk= substr($this->bytes, $this->offset, $maxLen);
        $this->offset+= $maxLen;
        return $chunk;
      }
    }');
  }

  /**
   * Creates a new TdsDataStream instance
   *
   * @param   string bytes
   * @param   int packetSize default 512
   * @return  rdbms.tds.TdsDataStream
   */
  public function newDataStream($bytes= '', $packetSize= 512) {
    return new TdsDataStream(self::$sock->newInstance($bytes), $packetSize);
  }
  
  /**
   * Creates a TDS packet header with a given length
   *
   * @param   int length length of data
   * @param   bool last default TRUE
   * @return  string
   */
  protected function headerWith($length, $last= true) {
    return pack('CCnnCc', 0x04, $last ? 0x01 : 0x00, $length + 8, 0x00, 0x00, 0x00);
  }

  #[Test, Expect(TdsProtocolException::class)]
  public function nullHeader() { 
    $this->newDataStream(null)->read(1);
  }
  
  #[Test]
  public function readOneZeroLength() { 
    Assert::equals('', $this->newDataStream($this->headerWith(0))->read(1));
  }

  #[Test]
  public function readAllZeroLength() { 
    Assert::equals('', $this->newDataStream($this->headerWith(0))->read(-1));
  }

  #[Test]
  public function readLength() { 
    Assert::equals('Test', $this->newDataStream($this->headerWith(4).'Test')->read(4));
  }

  #[Test]
  public function readMore() { 
    Assert::equals('Test', $this->newDataStream($this->headerWith(4).'Test')->read(1000));
  }

  #[Test]
  public function readAll() { 
    Assert::equals('Test', $this->newDataStream($this->headerWith(4).'Test')->read(-1));
  }

  #[Test]
  public function readLengthSpanningTwoPackets() {
    $packets= (
      $this->headerWith(2, false).'Te'.
      $this->headerWith(2, true).'st'
    );
    Assert::equals('Test', $this->newDataStream($packets)->read(4));
  }

  #[Test]
  public function readMoreSpanningTwoPackets() {
    $packets= (
      $this->headerWith(2, false).'Te'.
      $this->headerWith(2, true).'st'
    );
    Assert::equals('Test', $this->newDataStream($packets)->read(1000));
  }

  #[Test]
  public function readAllSpanningTwoPackets() {
    $packets= (
      $this->headerWith(2, false).'Te'.
      $this->headerWith(2, true).'st'
    );
    Assert::equals('Test', $this->newDataStream($packets)->read(-1));
  }

  #[Test]
  public function getString() {
    $str= $this->newDataStream($this->headerWith(9)."\x04T\x00e\x00s\x00t\x00");
    Assert::equals('Test', $str->getString($str->getByte()));
  }

  #[Test]
  public function getToken() {
    $str= $this->newDataStream($this->headerWith(1)."\x07");
    Assert::equals("\x07", $str->getToken());
  }

  #[Test]
  public function getByte() {
    $str= $this->newDataStream($this->headerWith(1)."\x07");
    Assert::equals(0x07, $str->getByte());
  }

  #[Test]
  public function getShort() {
    $str= $this->newDataStream($this->headerWith(2)."\x07\x08");
    Assert::equals(0x0807, $str->getShort());
  }

  #[Test]
  public function getLong() {
    $str= $this->newDataStream($this->headerWith(4)."\x05\x06\x07\x08");
    Assert::equals(0x8070605, $str->getLong());
  }

  #[Test]
  public function get() {
    $str= $this->newDataStream($this->headerWith(4)."\x05\x06\x07\x08");
    Assert::equals(
      ['length' => 0x05, 'flags' => 0x06, 'state' => 0x0807],
      $str->get("Clength/Cflags/vstate", 4)
    );
  }

  #[Test]
  public function beginReturnsMessageType() {
    $str= $this->newDataStream($this->headerWith(1)."\xAA");
    Assert::equals(0x04, $str->begin());
  }

  #[Test]
  public function beginDoesNotDiscardFirstByte() {
    $str= $this->newDataStream($this->headerWith(1)."\xAA");
    $str->begin();
    Assert::equals("\xAA", $str->getToken());
  }

  #[Test]
  public function beginDoesNotDiscardFirstBytes() {
    $str= $this->newDataStream($this->headerWith(2)."\xAA\xA2");
    $str->begin();
    Assert::equals("\xAA", $str->getToken());
    Assert::equals("\xA2", $str->getToken());
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '/must be at least 9/')]
  public function illegalPacketSize() {
    $this->newDataStream('', 1);
  }

  #[Test]
  public function writeBytes() {
    $socket= self::$sock->newInstance();
    $str= new TdsDataStream($socket);
    $str->write(0x04, 'Login');

    Assert::equals(new Bytes($this->headerWith(5).'Login'), new Bytes($socket->bytes));
  }

  #[Test]
  public function writeBytesSpanningMultiplePackets() {
    $socket= self::$sock->newInstance();
    $str= new TdsDataStream($socket, 10);
    $str->write(0x04, 'Test');

    Assert::equals(
      new Bytes($this->headerWith(2, false).'Te'.$this->headerWith(2, true).'st'),
      new Bytes($socket->bytes)
    );
  }
}