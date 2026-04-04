<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class Custom256Test extends TestCase
{
    private function getKeyStream(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    //Every test a different instance in time
    private function instancePswKey(string $seedPhrase = "deterministic validation", bool $hasKey = true) : PswKey {
        return new PswKey(
            $this->getKeyStream($seedPhrase, $hasKey)
        );
    }

    private function getBase100UTF() : string {
        return "0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°";
    }

    private function getSingleBytes() : array {
        //given order is => !\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0987654321
        return [
            "\x2b","\x2f","\x21","\x22","\x23","\x24","\x25","\x26","\x27","\x28","\x29","\x2a","\x2c","\x2d","\x2e",
            "\x3a","\x3b","\x3d","\x3f","\x40","\x5b","\x5c","\x5d","\x5e","\x5f","\x60","\x7b","\x7c","\x7d","\x7e",
            "\xa3","\xa7","\xa8","\xb2","\xb3","\xb4","\xb5","\xb0","\x42","\x43","\x44","\x45","\x46","\x47","\x48",
            "\x4a","\x4b","\x4c","\x4d","\x4e","\x50","\x51","\x52","\x53","\x54","\x55","\x56","\x57","\x58","\x59",
            "\x5a","\x61","\x62","\x63","\x64","\x65","\x66","\x67","\x68","\x69","\x6a","\x6b","\x6d","\x6e","\x6f",
            "\x70","\x71","\x72","\x73","\x74","\x75","\x76","\x77","\x78","\x79","\x7a","\x30","\x49","\x4f","\x6c",
            "\x31","\x32","\x33","\x34","\x35","\x36","\x37","\x38","\x39","\x41"
        ];
    }

    public function test_all_possible_custom(): void
    {
        //Prepare array 255 allowed Single bytes
        $singleBytes255 = [];
        for ($i=1; $i < 256; $i++) { 
            $singleBytes255[] = chr($i);
        }

        $pswKey = $this->instancePswKey();        

        $prev = "";
        //Run custom base4 to base255 and shuffle the single bytes (all uses different derived keys)
        for ($i=4; $i < count($singleBytes255); $i++) { 

            $encode = $pswKey
                ->from(100)
                ->customTo($singleBytes255, $i, true) //shuffle true
                ->convert(
                    transcode::getISO($this->getBase100UTF())
                );

            //prove that each is different from another
            if($i === 4) {
                $prev = $encode;
            }
            elseif($i > 4) {
                $this->assertNotEquals(
                    $prev,
                    $encode
                );

                $prev = $encode;
            }

            //check reverse            
            $decode = $pswKey
                ->customFrom($singleBytes255, $i, true) //shuffle true
                ->to(100)
                ->convert(
                    $encode
                );

            $this->assertEquals(
                $this->getBase100UTF(),
                transcode::getUTF($decode)
            );
        }
    }

    public function test_customKey_ok(): void
    {
        $pswKey = $this->instancePswKey();  

        //Get man single bytes in a different order than default order
        $arrSingleBytes = $this->getSingleBytes();

        //custom to() without customkey
        $encodeWithout = $pswKey
            ->from(100)
            ->customTo($arrSingleBytes, 100) //shuffle true
            ->convert(
                transcode::getISO($this->getBase100UTF())
            );

        //custom to() with set customkey
        $pswKey->setCustomKey(
            random_bytes(32) //randomly customkey | the order is always important
        );
        $pswKey->customTo($arrSingleBytes, 100); 

        $encodeWith = $pswKey
            ->from(100)
            ->convert(
                transcode::getISO($this->getBase100UTF())
            );

        $this->assertNotEquals(
            $encodeWithout,
            $encodeWith
        );
    }

    public function test_customKey_fail(): void {
        $pswKey = $this->instancePswKey();  

        //Get man single bytes in a different order than default order
        $arrSingleBytes = $this->getSingleBytes();

        //custom to() with set customkey
        $pswKey->setCustomKey("Remove Customkey before translate");

        //custom to() without customkey
        $encode = $pswKey
            ->customTo($arrSingleBytes, 32) //It doesn't matter if to() comes first
            ->from(100)
            ->convert(
                transcode::getISO($this->getBase100UTF())
            );

        $pswKey->resetCustomKey(); //remove customKey => not same alphabet-shuffle as above if removed

        $decode = $pswKey
            ->to(100) 
            ->customFrom($arrSingleBytes, 32)
            ->convert(
                transcode::getISO($encode)
            );

        $this->assertNotEquals(
            transcode::getISO($this->getBase100UTF()),
            $decode
        );
    }
}