<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class Base64Test extends TestCase
{
    private function getKeyStream(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

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
        $decode = $pswKey->from(64)->to(100)->convert("");
        
        $this->assertEmpty($decode);

        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_one_symbol_fail(): void
    {
        $pswKey = $this->instancePswKey();
        $decode = $pswKey->from(64)->to(100)->convert("k");
        
        $this->assertEmpty($decode);

        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_randomly64_fail(): void
    {
        $pswKey = $this->instancePswKey();
        $base64 = $pswKey
            ->from(100)
            ->to(64)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        //New instance in time (The previous base32 cannot be valid in the new instance)
        $pswKey = $this->instancePswKey();
        $decode = $pswKey
            ->from(64)
            ->to(100)
            ->convert($base64);
        
        $this->assertNotEquals(
            $this->getBase100UTF(),
            $decode
        ); 
        
        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_base64_to_base64(): void
    {
        $pswKey = $this->instancePswKey();
        $base64 = $pswKey
            ->from(100)
            ->to(64)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        $fullCheck64 = $pswKey->from(64)->to(64)->convert($base64);
        $this->assertNotEmpty($fullCheck64);
    }
}