<?php namespace rdbms\unittest\tds;

use lang\{ClassLoader, IllegalArgumentException};
use peer\Socket;
use rdbms\tds\{TdsDataStream, TdsProtocolException};
use unittest\{BeforeClass, Expect, Test, TestCase};
use util\Bytes;

/**
 * TestCase
 *
 * @see   xp://rdbms.tds.TdsDataStream
 */
class TdsDataStreamTest extends TestCase {
  protected static $sock;

  /**
   * Defines the mock socket class necessary for these tests
   */
  #[BeforeClass]
  public static function mockSocket() {
    self::$sock= ClassLoader::defineClass('rdbms.unittest.tds.MockTdsSocket', Socket::class, [], '{
      public $bytes;
      protected $offset= 0;
      
      public function __construct($bytes= "") {
        $this->bytes= $bytes;
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

  /**
   * Assertion helper
   *
   * @param   string bytes
   * @param   rdbms.tds.TdsDataStream str
   * @throws  unittest.AssertionFailedError
   */
  protected function assertBytes($bytes, $str) {
    $field= typeof($str)->getField('sock')->setAccessible(true);
    $this->assertEquals(new Bytes($bytes), new Bytes($field->get($str)->bytes));
  }

  #[Test, Expect(TdsProtocolException::class)]
  public function nullHeader() { 
    $this->newDataStream(null)->read(1);
  }
  
  #[Test]
  public function readOneZeroLength() { 
    $this->assertEquals('', $this->newDataStream($this->headerWith(0))->read(1));
  }

  #[Test]
  public function readAllZeroLength() { 
    $this->assertEquals('', $this->newDataStream($this->headerWith(0))->read(-1));
  }

  #[Test]
  public function readLength() { 
    $this->assertEquals('Test', $this->newDataStream($this->headerWith(4).'Test')->read(4));
  }

  #[Test]
  public function readMore() { 
    $this->assertEquals('Test', $this->newDataStream($this->headerWith(4).'Test')->read(1000));
  }

  #[Test]
  public function readAll() { 
    $this->assertEquals('Test', $this->newDataStream($this->headerWith(4).'Test')->read(-1));
  }

  #[Test]
  public function readLengthSpanningTwoPackets() {
    $packets= (
      $this->headerWith(2, false).'Te'.
      $this->headerWith(2, true).'st'
    );
    $this->assertEquals('Test', $this->newDataStream($packets)->read(4));
  }

  #[Test]
  public function readMoreSpanningTwoPackets() {
    $packets= (
      $this->headerWith(2, false).'Te'.
      $this->headerWith(2, true).'st'
    );
    $this->assertEquals('Test', $this->newDataStream($packets)->read(1000));
  }

  #[Test]
  public function readAllSpanningTwoPackets() {
    $packets= (
      $this->headerWith(2, false).'Te'.
      $this->headerWith(2, true).'st'
    );
    $this->assertEquals('Test', $this->newDataStream($packets)->read(-1));
  }

  #[Test]
  public function getString() {
    $str= $this->newDataStream($this->headerWith(9)."\x04T\x00e\x00s\x00t\x00");
    $this->assertEquals('Test', $str->getString($str->getByte()));
  }

  #[Test]
  public function getToken() {
    $str= $this->newDataStream($this->headerWith(1)."\x07");
    $this->assertEquals("\x07", $str->getToken());
  }

  #[Test]
  public function getByte() {
    $str= $this->newDataStream($this->headerWith(1)."\x07");
    $this->assertEquals(0x07, $str->getByte());
  }

  #[Test]
  public function getShort() {
    $str= $this->newDataStream($this->headerWith(2)."\x07\x08");
    $this->assertEquals(0x0807, $str->getShort());
  }

  #[Test]
  public function getLong() {
    $str= $this->newDataStream($this->headerWith(4)."\x05\x06\x07\x08");
    $this->assertEquals(0x8070605, $str->getLong());
  }

  #[Test]
  public function get() {
    $str= $this->newDataStream($this->headerWith(4)."\x05\x06\x07\x08");
    $this->assertEquals(
      ['length' => 0x05, 'flags' => 0x06, 'state' => 0x0807],
      $str->get("Clength/Cflags/vstate", 4)
    );
  }

  #[Test]
  public function beginReturnsMessageType() {
    $str= $this->newDataStream($this->headerWith(1)."\xAA");
    $this->assertEquals(0x04, $str->begin());
  }

  #[Test]
  public function beginDoesNotDiscardFirstByte() {
    $str= $this->newDataStream($this->headerWith(1)."\xAA");
    $str->begin();
    $this->assertEquals("\xAA", $str->getToken());
  }

  #[Test]
  public function beginDoesNotDiscardFirstBytes() {
    $str= $this->newDataStream($this->headerWith(2)."\xAA\xA2");
    $str->begin();
    $this->assertEquals("\xAA", $str->getToken());
    $this->assertEquals("\xA2", $str->getToken());
  }

  #[Test, Expect(['class' => IllegalArgumentException::class, 'withMessage' => '/must be at least 9/'])]
  public function illegalPacketSize() {
    $this->newDataStream('', 1);
  }

  #[Test]
  public function writeBytes() {
    $str= $this->newDataStream();
    $str->write(0x04, 'Login');
    $this->assertBytes($this->headerWith(5).'Login', $str);
  }

  #[Test]
  public function writeBytesSpanningMultiplePackets() {
    $str= $this->newDataStream('', 10);
    $str->write(0x04, 'Test');
    $this->assertBytes(
      $this->headerWith(2, false).'Te'.$this->headerWith(2, true).'st', 
      $str
    );
  }
}