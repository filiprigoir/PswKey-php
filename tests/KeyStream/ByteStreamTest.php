<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use Datetime;
use PHPUnit\Framework\TestCase;
use PswKey\Exception\ConfigurationException;
use PswKey\Service\KeyStream;

class ByteStreamTest extends TestCase
{
    private function getInstance(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function test_two_calls_different_results(): void
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

        //When no streamId is entered, streamID is encremented by 1 for each call
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

    public function test_enter_id_same_result(): void 
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            2048,
            "TestKey8"  
            //Enter 1 and the same stream will be generated again, as long as the context is the same.
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

    public function test_setcustom_different_result(): void
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

        $keyStream->setCustomKey("New Key entered"); //different custom key must generate different stream with same context and streamID

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

        $keyStream->resetCustomKey(); //clear custom key and it most be same as key1 again
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
 
    public function test_context_to_short_use_default(): void
    {
        $keyStream = $this->getInstance("Test a KeyStream");

        //Length of the context must be exacly 8 bytes (sodium extension role)
        $this->expectException(ConfigurationException::class);

        $key1 = '';
        $keyStream->byteStream(
            function($secretKey) use (&$key1) {
                $key1 = $secretKey;
            },
            32,
            "Test" //8 bits is required, exception
        );
    }
}