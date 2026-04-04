<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use Datetime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Util\Math\Calculation;
use PswKey\Exception\InputException;
use PswKey\Exception\ConfigurationException;

class DerivedKeyTest extends TestCase
{
    private function getInstance(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function testDerivedKey_negative_length(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");
        $keyLength = '';

        //Length should be between 1 and 256
        $this->expectException(InputException::class);

        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength) {
                $keyLength = strlen($secretKey);
            },
            -20,
            "TestKey1"
        );
    }

    public function testDerivedKey_out_length(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");
        $keyLength = '';

        //Length should be between 1 and 256
        $this->expectException(InputException::class);

        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength) {
                $keyLength = strlen($secretKey);
            },
            257,
            "TestKey2"
        );
    }

    public function testDerivedKey_wrongcontext_length(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");
        $keyLength = '';

        //Length of the context must be exacly 8 bytes (sodium extension role)
        $this->expectException(ConfigurationException::class);

        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength) {
                $keyLength = strlen($secretKey);
            },
            16,
            "TestKey"
        );
    }
    
    public function testDerivedKey_ok_length(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $keyLength1 = 0;
        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength1) {
                $keyLength1 = strlen($secretKey);
            },
            64,
            "TestKey3"
        );

        //Derived Key includes the possibility for Rejection Sampling (ie.: 64 will become 81)
        $requiredLength = Calculation::getFactor(64);
        $this->assertEquals($requiredLength, $keyLength1);
    }

    public function testDerivedKey_ok_differentOutput(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            64,
            "TestKey4"
        );

        $key2 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            64,
            "TestKey5"
        );

        $this->assertNotEquals(
            $key1,
            $key2,
        );
    }

    public function testDerivedKey_customTrue_differentOutput(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            64,
            "TestKey6",
            true
        );

        $keyStream->setCustomKey("New Key entered");

        $key2 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            64,
            "TestKey6",
            true
        );

        $this->assertNotEquals(
            $key1,
            $key2,
        );
    }

    public function testDerivedKey_customFalse_sameOutput(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            64,
            "TestKey7",
            false
        );

        $keyStream->setCustomKey("New Key entered");

        $key2 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            64,
            "TestKey7",
            false
        );

        $this->assertEquals(
            $key1,
            $key2,
        );
    }
}