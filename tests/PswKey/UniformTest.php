<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class UniformTest extends TestCase
{
    private function getKeyStream(string $seedPhrase, ?string $hasKey = null) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, empty($hasKey) === true ? $key : $hasKey);
    }

    private function instancePswKey(string $seedPhrase, ?string $hasKey = null) : PswKey {
        return new PswKey(
            $this->getKeyStream($seedPhrase, $hasKey)
        );
    }

    private function getDigits() : string {
        //610 random digits in string
        return '570417759969558280306741360185654482132421204132876178709721743832046592394771642905945187497632045939206449593'
            . '4193525932287415577563869103314624465875348131509413879740232979912824244315557791826573780792091598438962777695'
            . '5002349395450504829772536374864392362604722061759411404833377864333134609322026061613353119540633431016393408643'
            . '8277057152371535851531574685758608914740728572437584418129936751698931535487889531754923630016074639849888237813'
            . '5407053578507530764386481423886531641953951117768264007584273493519624206652783058761370027418974779443936114650'
            . '077134404239519071626161079423697016132120307704048';
    }

    public function test_max_number_uniform(): void
    {
        $maxBigEndianUniform = str_repeat("9", 406); //maximum possible uniform number (maximum or less always encode/decode)

        $pswkey = $this->instancePswKey("uniform number test", null); 

        $encode = $pswkey
            ->from(10)
            ->to(256)
            ->convert($maxBigEndianUniform);

        $status = $pswkey->status();
        $this->assertTrue(
            $status->valid
        );

        $this->assertNotNull($encode); 

        $this->assertNotEquals(
            $maxBigEndianUniform,
            $encode
        );

        $decode = $pswkey
            ->from(256)
            ->to(10)
            ->convert($encode);

        $this->assertEquals(
            $maxBigEndianUniform,
            $decode
        );
    }

    public function test_non_uniform10(): void
    {
        //The UTC timestamp "1776788605656989" is involved to simulate a non-uniform output
        //169 bytes is the limit for random digits
        //Note: entering free digits are not always possible, because the uniform format is designed to be deterministic output
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey("deterministic validation", "1777936375199218");
        
        $encode = $pswkey
            ->from(10)
            ->to(256)
            ->convert($digits);

        $this->assertTrue(
            empty($encode)
        );

        $status = $pswkey->status();
        $this->assertTrue(
            $status->invalid
        );

        $this->assertTrue(
            preg_match("/non-uniform/i", $status->internalMessage) === 1 ? true : false
        );
    }

    //Note: here it is possible beause less then 169 bytes. More than 169 bytes cannot be guaranteed during decoding, unless started from base256
    //Keep in mind, base100 is de encoded here, so it goes reverse to base256 decoded.
    public function test_base100_to_base256_ok(): void
    {
        $bytes100 = Transcode::getISO("0987654321abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!\"#$%&'()*+,-./:;\\=´?@[]^_`{|}~£§¨²³µ°");
        $pswkey = $this->instancePswKey("Uniform format is limited to 169 bytes. More bytes cannot be guaranteed during decoding, unless started from base256");

        $encode = $pswkey
            ->from(100)
            ->to(256)
            ->convert($bytes100);

        $this->assertNotNull($encode);

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $decode = $pswkey
            ->from(256)
            ->to(100)
            ->convert($encode);

        $this->assertEquals(
            $bytes100,
            $decode
        );
    }
}