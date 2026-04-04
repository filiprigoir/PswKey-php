<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use Datetime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Util\Math\Calculation;
use PswKey\Exception\InputException;
use PswKey\Exception\ConfigurationException;

class ByteStreamTest extends TestCase
{
    private function getInstance(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function testByteStream_everycall_differentOutput(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            2048,
            "TestKey8"
        );

        $key2 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            2048,
            "TestKey8"
        );

        $this->assertNotEquals(
            $key1,
            $key2,
        );
    }

    public function testByteStream_enterID_sameOutput(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            2048,
            "TestKey8"  
            //Enter 1 or leave it blank and the internal counter will increment automatically.
        );

        $key2 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            2048,
            "TestKey8",
            1
        );

        $this->assertEquals(
            $key1,
            $key2,
        );
    }

    public function testByteStream_setcustom_differentOutput(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            128,
            "TestKey9", 
            2
        );

        $keyStream->setCustomKey("New Key entered"); //Custom key has a different shuffling 

        $key2 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key2) {
                $key2 = $secretKey;
            },
            128,
            "TestKey9",
            2
        );

        $this->assertNotEquals(
            $key1,
            $key2,
        );

        $keyStream->resetCustomKey(); //Remove Custom key and it most be same as key1 again
        $key3 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key3) {
                $key3 = $secretKey;
            },
            128,
            "TestKey9",
            2
        );

        $this->assertEquals(
            $key1,
            $key3,
        );
    }

    public function testByteStream_wrongcontext_usesDefault(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            32,
            "Test" //8 bits is required, default is used 
        );

        $this->assertNotEmpty(
            $key1,
        );
    }

    public function testByteStream_nullcontext_usesDefault(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            32,
            null
        );

        $this->assertNotEmpty(
            $key1,
        );
    }
}