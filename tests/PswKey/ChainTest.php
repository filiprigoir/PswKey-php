<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Base\initArray;

class ChainTest extends TestCase
{
    public function test_chain(): void
    {
        //KeyStream + key in time
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        $pswKey = new PswKey(
            new KeyStream("Chain PswKey Test", $key)
        );

        //128 bytes + test front ord(0)
        $randomBytes = str_repeat("\0", 8) . random_bytes(120);

        //Base256 => base10
        $base10 = $pswKey->from(256)->to(10)->convert($randomBytes);

        //Base10 => base100
        $base100 = $pswKey->from(10)->to(100)->convert($base10);

        //Base100 => base32
        $base32 = $pswKey->from(100)->to(32)->convert($base100);

        //Base32 => base64
        $base64 = $pswKey->from(32)->to(64)->convert($base32);

        //base64 => Custom16
        $custom16 = $pswKey->customTo(initArray::_base62(), 16)->from(64)->convert($base64);

        //Custom16 => base62
        $base62 = $pswKey->customFrom(initArray::_base62(), 16)->to(62)->convert($custom16);

        //Base62 => base58
        $base58 = $pswKey->from(62)->to(58)->convert($base62);

        //Set CustomKey with above output
        $pswKey->setCustomKey($base58);

        //prepare custom255 bytes in array (note: ord(0) is not possible, used for padding only)
        $initBytes255 = [];
        for ($i=1; $i < 256; $i++) { 
            $initBytes255[$i] = chr($i); //array key start from 1 (inside CustomFrom it will be key 0)
        }

        //Base58 to custom255 with customKey
        $custom255 = $pswKey
            ->from(58)
            ->customTo($initBytes255, 255)
            ->convert($base58);

        //Set CustomFrom
        $pswKey->customFrom($initBytes255, 255);

        //Reset CustomKey for nex Custom18
        $pswKey->resetCustomKey();

        //prepare custom18 bytes
        $initBytes18 = [];
        for ($i=48; $i < 66; $i++) { 
            $initBytes18[$i] = chr($i);
        }

        //set Customkey 18
        $pswKey->customTo($initBytes18, 18, false); //not shuffled (resetCustomKey has no effect here because no shuffling)

        //convert custom255 to custom18
        $custom18 = $pswKey->convert($custom255);

        //custom18 to
        $base256 = $pswKey->customFrom($initBytes18, 18, false)->to(256)->convert($custom18);
        
        $this->assertEquals(
            $randomBytes,
            $base256
        );
    }
}