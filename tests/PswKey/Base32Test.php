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

    //Each instance has a different key, so the same base32 string will not be valid in different instances
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
        //it is not possible to enter a base32 string that was generated in a different instance, as the key is different in each instance in this setting
        $pswKey = $this->instancePswKey();
        $base32 = $pswKey
            ->from(100)
            ->to(32)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        //new instance with a different key, so the same base32 string will not be valid
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