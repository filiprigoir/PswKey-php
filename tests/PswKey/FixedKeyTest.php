<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;
use PswKey\Util\Char\Transcode;
use PswKey\Util\Base\initArray;

class FixedKeyTest extends TestCase
{
    private function getKeyStream(string $seedPhrase) : KeyStream {
        return new KeyStream($seedPhrase);
    }

    private function instancePswKey() : PswKey {
        return new PswKey(
            $this->getKeyStream("deterministic validation test") //No key in time, otherwise we have not the same results
        );
    }

    private function getText() : string {
        //Original message
        return "A deterministic validation library for context-aware verification of converted base/pswkey structures";
    }

    private function getConvertedBase10() : string {
        return "0195691692470282763320226064535861237634176620779352766007136866288068253803083878961707082722461153258404757788513707156366029745342197163944231958309135785961004650289321997985371972454496134944534221208839522083388378150079687465225235479809";
    }

    private function getConvertedBase100() : string {
        return "5bTow]!^aQ³8PYnzDla=µ}³#~*aP|VI}@WI{f3Zf:sµ|Zt8´,n{'hN#7q&|.u}!_g=r_oAClRzJdj:2D+´U@~r\$LK&RGgCsV9Cn%r³7A*³BfB:.+LI¨k8*j]\(";
    }

    private function getConvertedBase32() : string {
        return "QkMK2ppf0q9kwglME4wKDE0ztRUQggRcEkopQ2poKR9kD5UERKoqDkxRt3hyQEhQsRNpl5Uxz9w020yzt4hE9hMyQcgP45nzzkoPUfpsskNKopxn23EKboPl4Mkyq4PbKzxKl9yoz2PbgPNQlRyEz9phlgwqQpMKzlNhz9RlE3nQ49MxKgkqgP3234wUqxPc09qb";
    }

    private function getConvertedBase58() : string {
        return "ySa2J7HmxqUfjnKa2NTmevB2ohRXevkqWoyPMYVjGGEKjMa4wDhvZnTAdkkU5YXc8oCSNjo3EcBihEoK4tbdPDKFtuC9yKf18Fw4cSiKTeL1bYdbNXtBd6zDP53SPUeSFs1qCJRwjDpakaL81o8inLYENsfDVbftcWXcJyN1";
    }

    private function getConvertedBase62() : string {
        return "oSsCLYd3eBfbGqpBjIZmgB8EofTbBQV9YmumbKjxgF6PEBcKks0h97Opd7Iauf9HUuKtC0VXTsqEgvBqDk1RyIx4lP7HZ48ZUvVYkRQwiVl0SqidfxOsQSpr599aZMGT0nsdBYaQIUVdghszLNm43F4ef7huMDECTLgiOytY";
    }

    private function getConvertedBase64() : string {
        return "4tJPTvnnColUPThAPEuHWqI/aBNZxThixL6UP9MtVRZpo1u82i4Y2i6ckRMYnjL1Kzl8JNUgCcnMJrFHnR1URNwfwufD+P4/Pr672zSbhq+ykEopxrCqBs/GPqnr+v4tgP4HJR7c41s3/1JG+iSH71MJ3z/A7G6nRed";   
    }

    private function getConvertedCustom_32() : string {
        return "^~_cl66XP[$~W\"Y_xwWc°xPZQ,µ^\"\",mx~´6^l6´c,$~°5µx,c´[°~f,QKMk^xM^e,{6Y5µfZ\$WPlPkZQwMx\$M_k^m\"jw5hZZ~´jµX6ee~{c´6fhlKxcs´jYw_~k[wjscZfcY\$k´Zljs\"j{^Y,kxZ$6MY\"W[^6_cZY{MZ$,YxKh^w\$_fc\"~[\"jKlKwWµ[fjmP$[°";
    }

    public function test_from_base100(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Create single bytes first. Some chars are over 127 so use getISO
        $base100 = Transcode::getISO($this->getConvertedBase100());

        //Note: This repo only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $decode = $pswkey
            ->from(100)
            ->to(256)
            ->convert($base100);

        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_from_base10(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $base10 = Transcode::getISO($this->getConvertedBase10());

        $decode = $pswkey
            ->from(10)
            ->to(256)
            ->convert($base10);
            
        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_from_base32(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $base32 = $this->getConvertedBase32();

        $decode = $pswkey
            ->from(32)
            ->to(256)
            ->convert($base32);

        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_from_base58(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $base58 = $this->getConvertedBase58();

        $decode = $pswkey
            ->from(58)
            ->to(256)
            ->convert($base58);

        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_from_base62(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $base62 = $this->getConvertedBase62();

        $decode = $pswkey
            ->from(62)
            ->to(256)
            ->convert($base62);

        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_from_base64(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $base64 = $this->getConvertedBase64();

        $decode = $pswkey
            ->from(64)
            ->to(256)
            ->convert($base64);

        $this->assertEquals(
            $text,
            $decode
        );
    }

    public function test_from_custom32(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        $custom32 = Transcode::getISO($this->getConvertedCustom_32());

        $decode = $pswkey
            ->customFrom(initArray::_base100(), 32, true) //true = shuffles 100 char but take only 32 char 
            ->to(256)
            ->convert($custom32);

        $this->assertEquals(
            $text,
            $decode
        );
    }
}