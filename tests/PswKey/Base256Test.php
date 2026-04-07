<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;

//Base256 always returns a uniform representation
class Base256Test extends TestCase
{
    private function getKeyStream(string $seedPhrase, bool $hasKey) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $hasKey === true ? $key : '');
    }

    //Every test a different instance in time
    private function instancePswKey(string $seedPhrase = "deterministic validation", bool $hasKey = true) : PswKey {
        return new PswKey(
            $this->getKeyStream($seedPhrase, $hasKey)
        );
    }

    private function getText() : string {
        return "A deterministic validation library for context-aware verification of converted base/pswkey structures";
    }

    public function test_empty_fail(): void
    {
        $text = "";
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(256)
            ->to(100)
            ->convert($text);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->system);
    }

    public function test_updated_empty_fail(): void
    {
        $text = "";
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(256)
            ->to(100)
            ->convert($text);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->system);

        //Try again with allowed char A
        $encode = $pswkey
            ->from(100)
            ->to(256)
            ->convert("A");

        //Status is updated to new state
        $this->assertNotNull($encode);
        $status = $pswkey->status();
        $this->assertTrue($status->valid);
    }

    public function test_wrong_base_fail(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(256)
            ->to(32)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(62)
            ->to(256)
            ->convert($encode);

        $status = $pswkey->status();
        $this->assertFalse($status->valid);

        //is outcome of decode same as original digits again? Yes!
        $this->assertNotEquals(
            $text,
            $decode
        );
    }

    public function test_to_base10_ok(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(256)
            ->to(10)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(10)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_to_base32_ok(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(256)
            ->to(32)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(32)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_to_base58_ok(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(256)
            ->to(58)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(58)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_to_base62_ok(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(256)
            ->to(62)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(62)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_to_base64_ok(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Note: 256 accept single en multiple bytes with prefixes
        $encode = $pswkey
            ->from(256)
            ->to(64)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(64)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_to_base256_ok(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(256)
            ->to(256)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);
    }

    public function test_randomBytes_ok(): void
    {
        $text = random_bytes(100000);
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(256)
            ->to(64)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(64)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_padding_ok(): void
    {
        $bytesLen = 128;
        $paddingLen = 50;
        $text = str_repeat("\0", $paddingLen) . random_bytes($bytesLen);
        $pswkey = $this->instancePswKey();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $encode = $pswkey
            ->from(256)
            ->to(10)
            ->convert($text);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(10)
            ->to(256)
            ->convert($encode);

        //is outcome of decode same as original digits again? Yes!
        $this->assertEquals(
            $text,
            $decode
        );

        $this->assertEquals(
            $paddingLen+$bytesLen,
            strlen($decode)
        );
    }
}