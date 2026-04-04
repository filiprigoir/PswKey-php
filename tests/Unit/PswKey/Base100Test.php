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

    //For each test a different instance in time
    private function instancePswKey(string $seedPhrase = "deterministic validation", bool $hasKey = true) : PswKey {
        return new PswKey(
            $this->getKeyStream($seedPhrase, $hasKey)
        );
    }

    //Income UTC 100 printable characters
    private function getBase100UTF() : string {
        return "0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°";
    }

    //This service only accepts single bytes
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
        $this->assertNotNull($status->system);
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
        $this->assertNotNull($status->system);

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

    public function test_UTF_fail(): void
    {
        $base100 = $this->getBase100UTF();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string. 
        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->system);
    }

    public function test_UnknownChar_fail(): void
    {
        $base100 = Transcode::getISO("ç"); //to single byte as it should be
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($base100);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->system);
    }

    public function test_quickcheck_fail(): void
    {
        $digits = "<" . $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($digits);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->customer);
        
        $this->assertTrue(
            preg_match("/quickcheck/i", $status->system) === 1 ? true : false
        );
    }

    public function test_process_fail(): void
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
            preg_match("/process/i", $status->system) === 1 ? true : false
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

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base32_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base58_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base62_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base64_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $base100,
            $decode
        );
    }

    public function test_to_base100_ok(): void
    {
        $base100 = $this->getBase100ISO();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(100)
            ->to(100)
            ->convert($base100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $base100,
            $encode
        );
    }
}