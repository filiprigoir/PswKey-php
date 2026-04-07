<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class Base32Test extends TestCase
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

    public function test_empty_fail(): void
    {
        $pswKey = $this->instancePswKey();
        $decode = $pswKey->from(32)->to(100)->convert("");
        
        $this->assertEmpty($decode);

        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_one_symbol_fail(): void
    {
        $pswKey = $this->instancePswKey();
        $decode = $pswKey->from(32)->to(100)->convert("k");
        
        $this->assertEmpty($decode);

        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_randomly32_fail(): void
    {
        //In this service it is not possible to enter a randomly or used base32 string.
        $pswKey = $this->instancePswKey();
        $base32 = $pswKey
            ->from(100)
            ->to(32)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        //New instance in time (The previous base32 cannot be valid in the new instance)
        $pswKey = $this->instancePswKey();
        $decode = $pswKey
            ->from(32)
            ->to(100)
            ->convert($base32);
        
        $this->assertNotEquals(
            $this->getBase100UTF(),
            $decode
        ); 
        
        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_base32_to_base32(): void
    {
        $pswKey = $this->instancePswKey();
        $base32 = $pswKey
            ->from(100)
            ->to(32)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        $fullCheck32 = $pswKey->from(32)->to(32)->convert($base32);
        $this->assertNotEmpty($fullCheck32);
    }
}