<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use Datetime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Exception\ConfigurationException;

class DerivedKeyTest extends TestCase
{
    private function getInstance(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function test_negative_length_gives_exception(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");
        $keyLength = '';

        //Length most be between 1 and 256
        $this->expectException(ConfigurationException::class);

        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength) {
                $keyLength = strlen($secretKey);
            },
            -20,
            "TestKey1"
        );
    }

    public function test_out_of_range_length_exception(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");
        $keyLength = '';

        //Length cannot be lower than 16 bytes
        $this->expectException(ConfigurationException::class);

        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength) {
                $keyLength = strlen($secretKey);
            },
            5,
            "TestKey2"
        );
    }

    public function test_wrong_context_exception(): void
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
            "TestKey" //must be 8 bytes, otherwise exception is thrown
        );
    }
    
    public function test_correct_length_ok(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $keyLength = 0;
        $keyStream->derivedKey(
            function($secretKey) use (&$keyLength) {
                $keyLength = strlen($secretKey);
            },
            65,
            "TestKey3"
        );

        $this->assertEquals(1 * 64 + 16, $keyLength);
    }

    public function test_other_context_gives_different_result(): void
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

    public function test_customKey_true_different_result(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            64,
            "TestKey6",
            true //<= custom key enabled
        );

        $keyStream->setCustomKey("New Key entered");

        $key2 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            64,
            "TestKey6",
            true //<= custom key enabled
        );

        $this->assertNotEquals(
            $key1,
            $key2,
        );
    }

    public function test_customKey_true_same_result(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            64,
            "TestKey7",
            false //<= custom key disabled, even if custom key is set, it should not affect the result
        );

        $keyStream->setCustomKey("New Key entered");

        $key2 = '';
        $keyStream->derivedKey(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            64,
            "TestKey7",
            false //<= custom key disabled, even if custom key is set, it should not affect the result
        );

        $this->assertEquals(
            $key1,
            $key2,
        );
    }
}