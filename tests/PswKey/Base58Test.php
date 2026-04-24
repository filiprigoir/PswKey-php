<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;

class Base58Test extends TestCase
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
        $decode = $pswKey->from(58)->to(100)->convert("");
        
        $this->assertEmpty($decode);

        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_one_symbol_fail(): void
    {
        $pswKey = $this->instancePswKey();
        $decode = $pswKey->from(58)->to(100)->convert("k");
        
        $this->assertEmpty($decode);

        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_randomly58_fail(): void
    {
        $pswKey = $this->instancePswKey();
        $base58 = $pswKey
            ->from(100)
            ->to(58)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        $pswKey = $this->instancePswKey();
        $decode = $pswKey
            ->from(58)
            ->to(100)
            ->convert($base58);
        
        $this->assertNotEquals(
            $this->getBase100UTF(),
            $decode
        ); 
        
        $status = $pswKey->status();
        $this->assertFalse($status->valid);
    }

    public function test_base58_to_base58(): void
    {
        $pswKey = $this->instancePswKey();
        $base58 = $pswKey
            ->from(100)
            ->to(58)
            ->convert(
                transcode::getISO($this->getBase100UTF())
        );

        $fullCheck58 = $pswKey->from(58)->to(58)->convert($base58);
        $this->assertNotEmpty($fullCheck58);
    }
}