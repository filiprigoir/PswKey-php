<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class OrderTest extends TestCase
{
    public function getInstance(string $key) : PswKey {
        return new PswKey(
            new KeyStream("Engine Performance Test", $key)
        );
    }

    public function test_base_order(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        //Encode
        $pswKey = $this->getInstance($key);

        $base100 = Transcode::getISO("0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°");
        
        $encoded1 = $pswKey->from(100)->to(32)->convert($base100);
        $encoded2 = $pswKey->from(100)->to(58)->convert($base100);

        unset($pswKey);
        //Decode (simulate as distance call and set instance)
        $pswKey = $this->getInstance($key);

        $decoded1 = $pswKey->from(58)->to(100)->convert($encoded2);

        //set customkey
        $pswKey->setCustomKey("has no effect on base from & to"); 

        $decoded2 = $pswKey->from(32)->to(100)->convert($encoded1);

        //Orginal compare
        $this->assertEquals(
            $decoded1,
            $decoded2
        );
    }


    public function test_custom_order(): void
    {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

        //Encode
        $pswKey = $this->getInstance($key);

        $singleBhytes = ["\x2b","\x2f","\x21","\x22","\x23","\x24","\x25","\x26","\x27","\x28","\x29","\x2a","\x2c","\x2d","\x2e",
            "\x3a","\x3b","\x3d","\x3f","\x40","\x5b","\x5c","\x5d","\x5e","\x5f","\x60","\x7b","\x7c","\x7d","\x7e",
            "\xa3","\xa7","\xa8","\xb2","\xb3","\xb4","\xb5","\xb0","\x42","\x43","\x44","\x45","\x46","\x47","\x48",
            "\x4a","\x4b","\x4c","\x4d","\x4e","\x50","\x51","\x52","\x53","\x54","\x55","\x56","\x57","\x58","\x59",
            "\x5a","\x61","\x62","\x63","\x64","\x65","\x66","\x67","\x68","\x69","\x6a","\x6b","\x6d","\x6e","\x6f",
            "\x70","\x71","\x72","\x73","\x74","\x75","\x76","\x77","\x78","\x79","\x7a","\x30","\x49","\x4f","\x6c",
            "\x31","\x32","\x33","\x34","\x35","\x36","\x37","\x38","\x39","\x41"];

        $base100 = Transcode::getISO("0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°");

        $encoded1 = $pswKey
            ->from(100)
            ->customTo($singleBhytes, 58) //shuffle true
            ->convert($base100);

        $pswKey->setCustomKey("custom key effected custom base"); //Second was include customkey, so set first

        $encoded2 = $pswKey
            ->from(100)
            ->customTo($singleBhytes, 32) //shuffle true
            ->convert($base100);

        unset($pswKey);
        //Decode (simulate as distance call and set instance)
        $pswKey = $this->getInstance($key);

        //note: not same as abov, so it most be fail
        $pswKey->setCustomKey("custom key effected custom base");

        //Ok
        $decoded1 = $pswKey
            ->customFrom($singleBhytes, 32)
            ->to(100) 
            ->convert($encoded2);

        $this->assertEquals(
            $base100,
            $decoded1
        );

        $pswKey->resetCustomKey(); //the first was without custom key, so back to default first

        //Fails, not the same shuffle!
        $decoded2 = $pswKey
            ->customFrom($singleBhytes, 58)
            ->to(100)
            ->convert($encoded1);

        $this->assertEquals(
            $base100,
            $decoded2
        );
    }
}