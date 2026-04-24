<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class Base100Test extends TestCase
{
    private function getKeyStream(string $seedPhrase, bool $hasKey) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $hasKey === true ? $key : '');
    }

    private function instancePswKey(string $seedPhrase = "deterministic validation", bool $hasKey = true) : PswKey {
        return new PswKey(
            $this->getKeyStream($seedPhrase, $hasKey)
        );
    }

    private function getBase100UTF() : string {
        return "0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°";
    }

    private function getBase100ISO() : string {
        return  Transcode::getISO($this->getBase100UTF());
    }

    public function test_empty_fail(): void
    {
        $base100 = "";
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->internalMessage);
    }

    public function test_updated_empty_fail(): void
    {
        $base100 = "";
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->internalMessage);

        //Try again with allowed char A
        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert("A");

        //Status is updated to new state
        $this->assertNotNull($encode);
        $status = $pswkey->status();
        $this->assertTrue($status->valid);
    }

    public function test_UTF_failed(): void
    {
        $base100 = $this->getBase100UTF();
        $pswkey = $this->instancePswKey();

        //the UTF string contains multiple bytes with prefixes in string, so it is not valid. 
        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->internalMessage);
    }

    public function test_unknownChar_failed(): void
    {
        $base100 = Transcode::getISO("ç"); //convert to single byte first
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->internalMessage);
    }

    public function test_quickcheck_failed(): void
    {
        $digits = "<" . $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($digits);

        $this->assertNull($encode);

        $status = $pswkey->status(); 
        $this->assertNotNull($status->clientMessage);
        
        $this->assertTrue(
            preg_match("/quickcheck/i", $status->internalMessage) === 1 ? true : false
        );
    }

    public function test_process_failed(): void
    {
        $digits = $this->getBase100ISO() . "ç" . $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($digits);

        $this->assertNull($encode);
        $status = $pswkey->status();
        $this->assertTrue(
            preg_match("/process/i", $status->internalMessage) === 1 ? true : false
        );
    }

    public function test_ISO_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(32)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);
    }

    public function test_to_base10_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($encode);

        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base32_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(32)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(32)
            ->to(100)
            ->convert($encode);

        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base58_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(58)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(58)
            ->to(100)
            ->convert($encode);

        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base62_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(62)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(62)
            ->to(100)
            ->convert($encode);

        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base64_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(64)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(64)
            ->to(100)
            ->convert($encode);

        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base100_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(100)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $this->assertEquals(
            $base100,
            $encode
        );
    }
}