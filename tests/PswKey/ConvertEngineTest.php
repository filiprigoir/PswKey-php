<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;

class ConvertEngineTest extends TestCase
{
    public function getInstance(string $key) : PswKey {
        return new PswKey(
            new KeyStream("Engine Performance Test", $key)
        );
    }

    public function test_encodeGMP_longEndianChunkOk(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        $pswKey = $this->getInstance($key);

        //hard coded default setting for tests
        $pswKey->gmpEnable(true);
        $pswKey->longEndianChunk(true);

        $randomBytes = random_bytes(10000);
        $encoded = $pswKey->from(256)->to(58)->convert($randomBytes);

        unset($pswKey);
        //Decode (simulate distance call)
        $pswKey = $this->getInstance($key);
        //hard coded default setting
        $pswKey->gmpEnable(true);
        $pswKey->longEndianChunk(true);

        $decoded = $pswKey->from(58)->to(256)->convert($encoded);

        $this->assertEquals(
            $randomBytes,
            $decoded
        );
    }

    public function test_encodeGMP_shortEndianChunkOk(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        $pswKey = $this->getInstance($key);

        //hard coded default setting
        $pswKey->gmpEnable(true);
        $pswKey->longEndianChunk(false);

        $randomBytes = random_bytes(10000);
        $encoded = $pswKey->from(256)->to(32)->convert($randomBytes);

        unset($pswKey);
        //Decode (simulate distance call)
        $pswKey = $this->getInstance($key);
        //hard coded default setting
        $pswKey->gmpEnable(true);
        $pswKey->longEndianChunk(false);

        $decoded = $pswKey->from(32)->to(256)->convert($encoded);

        $this->assertEquals(
            $randomBytes,
            $decoded
        );
    }

    public function test_encodeBC_longEndianChunkOk(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        $pswKey = $this->getInstance($key);

        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(true);

        $randomBytes = random_bytes(10000);
        $encoded = $pswKey->from(256)->to(100)->convert($randomBytes);

        unset($pswKey);
        //Decode (simulate as distance call and set instance)
        $pswKey = $this->getInstance($key);
        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(true);

        $decoded = $pswKey->from(100)->to(256)->convert($encoded);

        $this->assertEquals(
            $randomBytes,
            $decoded
        );
    }

    public function test_encodeBC_shortEndianChunkOk(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        $pswKey = $this->getInstance($key);

        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(false);

        $randomBytes = random_bytes(10000);
        $encoded = $pswKey->from(256)->to(10)->convert($randomBytes);

        unset($pswKey);
        //Decode (simulate as distance call and set instance)
        $pswKey = $this->getInstance($key);
        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(false);

        $decoded = $pswKey->from(10)->to(256)->convert($encoded);

        $this->assertEquals(
            $randomBytes,
            $decoded
        );
    }

    public function test_endianChunk_notEquals_fail(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        $pswKey = $this->getInstance($key);

        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(true);

        $randomBytes = random_bytes(10000);
        $encoded = $pswKey->from(256)->to(58)->convert($randomBytes);

        unset($pswKey);
        //Decode (simulate as distance call and set instance)
        $pswKey = $this->getInstance($key);
        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(false);

        $decoded = $pswKey->from(58)->to(256)->convert($encoded);

        $this->assertNotEquals(
            $randomBytes,
            $decoded
        );
    }

    public function test_short_bc_disableFFI(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        $pswKey = $this->getInstance($key);

        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(false);
        $pswKey->enabledFFI(false);

        $randomBytes = random_bytes(10000);
        $encoded = $pswKey->from(256)->to(58)->convert($randomBytes);

        unset($pswKey);
        //Decode (simulate as distance call and set instance)
        $pswKey = $this->getInstance($key);
        //hard coded default setting
        $pswKey->gmpEnable(false);
        $pswKey->longEndianChunk(false);
        //$pswKey->enabledFFI(false) = disabled => surely it should be the same for both FFI and PHP
        //Note: if FFI is not available, every test is using PHP

        $decoded = $pswKey->from(58)->to(256)->convert($encoded);

        $this->assertEquals(
            $randomBytes,
            $decoded
        );
    }
}