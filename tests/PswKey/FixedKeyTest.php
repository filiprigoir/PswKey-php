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
        return "4745230109642248634440516889918019976399508540611087636888174985145349047762557713265088556651762091049578346175316588431185222138999821017974972480162941138219367618141098289069652425387426171274919298407579874081778113433690494207518741643593";
    }

    private function getConvertedBase100() : string {
        return "5bTow]!^aQ³8PYnzDla=µ}³#~*aP|VI}@WI{f3Zf:sµ|Zt8´,n{'hN#7q&|.u}!_g=r_oAClRzJdj:2D+´U@~r\$LK&RGgCsV9Cn%r³7A*³BfB:.+LI¨k8*j]\(";
    }

    private function getConvertedBase32() : string {
        return "zkTm8yya7PDkbF5TRtbmvR7Exh2zFFhNRkfyz8yfmhDkvU2RhmfPvkjhxuIKzRIz3hCy5U2jEDb787KExtIRDITKzNF4tUWEEkf42ay33kCmfyjW8uRmqf45tTkKPt4qmEjm5DKfE84qF4Cz5hKREDyI5FbPzyTmE5CIEDh5RuWztDTjmFkPF4u8utb2Pj4N7DPz";
    }

    private function getConvertedBase58() : string {
        return "WcwDj27JCM3v5RVwDyUJLX1DQN9KLXYMZQWnatB5qqrV5awepmNXTRUHiYY3ztKP6Qgcy5QurP18NrQVedoinmVkdxgfWVvh6kpePc8VULFhotioyKd1i4Gmnzucn3LckShMgj9p5mswYwF6hQ68RFtrySvmBovdPZKPjWyh";
    }

    private function getConvertedBase62() : string {
        return "25ad7kQvuYwOGcNYxWKqBYHj2wlOYm3UkqtqOsxXBCy0jYhsSaJ6Ue9NQeWptwUnAts1dJ34lacjBTYcVSZrLWXoD0enKoHKAT3kSrmfR3DJ5cRQwX9am5NzMUUpKFGlJgaQYkpmWA3QB6aI78qovCouwe6tFVjdl7BR9L1k";
    }

    private function getConvertedBase64() : string {
        return "RZ0iXyNNaxFuiXQziGljLo+1WJ4m3XQr3KguiPYZHCmhx6lq7rRn7rgf5CYnNIK6bcFq04uVafNY0SpjNC6uC4OwOlweEiR1iSgB7cA/QoEk5Gxh3SaoJT12ioNSEyRZViRj0CBfR6Tt1602ErAjB6Y0tc1zB2gNCv9";   
    }

    private function getConvertedCustom_32() : string {
        return "9{'3:&&X}s~{l_T'GMl3!G}(¨S£9__S=G{i&9:&i3S~{!^£GS3is!{,S¨u6§9G69`So&T^£,(~l}:}§(¨M6G~6'§9=_OM^x(({iO£X&``{o3i&,x:uG34iOTM'{§sMO43(,3T~§i(:O4_Oo9TS§G(~&6T_ls9&'3(To6(~STGux9M~',3_{s_Ou:uMl£s,O=}~sx";
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