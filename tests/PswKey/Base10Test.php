<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;

class Base10Test extends TestCase
{
    private function getKeyStream(string $seedPhrase, bool $hasKey) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $hasKey === true ? $key : '');
    }

    private function instancePswKey(string $seedPhrase = "deterministic validation", bool $hasKey = true) : PswKey {
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

    public function test_pair_fail(): void
    {
        $digits = "526307842"; //9 => must be 10 (ie.: 0526307842 or 5263078402)
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->internalMessage);
    }

    public function test_empty_fail(): void
    {
        $digits = "";
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->clientMessage);
    }

    public function test_quickcheck_fail(): void
    {
        $digits = "ma" . $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertNotNull($status->clientMessage);
        
        $this->assertTrue(
            preg_match("/quickcheck/i", $status->internalMessage) === 1 ? true : false
        );
    }

    public function test_process_fail(): void
    {
        $digits = $this->getDigits() . "ma" . $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNull($encode);

        $status = $pswkey->status();
        $this->assertTrue(
            preg_match("/process/i", $status->internalMessage) === 1 ? true : false
        );
    }

    public function test_seedphrase_fail(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey("deterministic validation", false); //false here, otherwise different result in time

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $pswkey2 = $this->instancePswKey("Deterministic validation", false); //deterministic is wrote as Deterministic (d => D)
        
        $decode = $pswkey2
            ->from(100)
            ->to(10)
            ->convert($encode);

        $this->assertNotEquals(
            $digits,
            $decode
        );
    }

    public function test_bitshift_fail(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey("deterministic validation", false); //false here, otherwise different result in time

        $encode = $pswkey
            ->from(10)
            ->to(32)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $pswkey2 = $this->instancePswKey("Deterministic validation", false); //deterministic is wrote as Deterministic (d => D)
        
        $decode = $pswkey2
            ->from(32)
            ->to(10)
            ->convert($encode);

        $this->assertNotEquals(
            $digits,
            $decode
        );

        $status = $pswkey2->status();
        $this->assertFalse($status->valid);
    }

    public function test_compute_fail(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey("deterministic validation", false); //false here, otherwise different result in time

        $encode = $pswkey
            ->from(10)
            ->to(62)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $pswkey2 = $this->instancePswKey("Deterministic validation", false); //deterministic is wrote as Deterministic (d => D)
        
        $decode = $pswkey2
            ->from(62)
            ->to(10)
            ->convert($encode);


        $this->assertNotEquals(
            $digits,
            $decode
        );

        $status = $pswkey2->status();
        $this->assertFalse($status->valid);
    }

    public function test_newInstance_ok(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey("deterministic validation", false); //false here, otherwise different result in time

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $status = $pswkey->status();
        $this->assertTrue($status->valid);

        $pswkey2 = $this->instancePswKey("deterministic validation", false);

        $decode = $pswkey2
            ->from(100)
            ->to(10)
            ->convert($encode);

        $this->assertEquals(
            $digits,
            $decode
        );

        $status = $pswkey2->status();
        $this->assertFalse($status->invalid);
    }
    
    public function test_to_base100(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(100)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $decode = $pswkey
            ->from(100)
            ->to(10)
            ->convert($encode);
!
        $this->assertEquals(
            $digits,
            $decode
        );
    }

    public function test_to_base58(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(58)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $decode = $pswkey
            ->from(58)
            ->to(10)
            ->convert($encode);

        $this->assertEquals(
            $digits,
            $decode
        );
    }

    public function test_to_base62(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(62)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $decode = $pswkey
            ->from(62)
            ->to(10)
            ->convert($encode);

        $this->assertEquals(
            $digits,
            $decode
        );
    }

    public function test_to_base64(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(64)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $decode = $pswkey
            ->from(64)
            ->to(10)
            ->convert($encode);

        $this->assertEquals(
            $digits,
            $decode
        );
    }

    public function test_to_base32(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(32)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertNotEquals(
            $digits,
            $encode
        );

        $decode = $pswkey
            ->from(32)
            ->to(10)
            ->convert($encode);

        $this->assertEquals(
            $digits,
            $decode
        );
    }

    public function test_to_base10(): void
    {
        $digits = $this->getDigits();
        $pswkey = $this->instancePswKey();

        $encode = $pswkey
            ->from(10)
            ->to(10)
            ->convert($digits);

        $this->assertNotNull($encode);

        $this->assertEquals(
            $digits,
            $encode
        );
    }
}