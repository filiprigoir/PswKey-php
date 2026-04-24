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
        return "8152497455918660544244360347699377405434672844118719540338592328204823759488329495586738325636760869754563821199857938782128863707341837746662403093169889953377927657208718962780793043076258591062694118449966194405940595789227231425361989916124";
    }

    private function getConvertedBase100() : string {
        return "5bTow]!^aQ³8PYnzDla=µ}³#~*aP|VI}@WI{f3Zf:sµ|Zt8´,n{'hN#7q&|.u}!_g=r_oAClRzJdj:2D+´U@~r\$LK&RGgCsV9Cn%r³7A*³BfB:.+LI¨k8*j]\(";
    }

    private function getConvertedBase32() : string {
        return "te2aw44vMpCeinW2Z1iaJZMY6gItnngfZej4tw4jagCeJqIZgajpJeSg6m97tZ9togh4WqISYCiMwM7Y619ZC927tfnF1qPYYejFIv4ooehaj4SPwmZaHjFW12e7p1FHaYSaWC7jYwFHnFhtWg7ZYC49Wnipt42aYWh9YCgWZmPt1C2SanepnFmwm1iIpSFfMCpW";
    }

    private function getConvertedBase58() : string {
        return "1Au83KDNd6YohyCu8X5NktE8FP7fkt96bF14TRUhqqMChTuwngPtsy5iL99YBRfrmFWAXhFJMrEcPMFCw2aL4gCz2QWZ1CoxmznwrAcC5kVxaRLaXf2ELGpg4BJA4YkAzHx6W37nhgeu9uVmxFmcyVRMXHogUao2rbfr31Xx";
    }

    private function getConvertedBase62() : string {
        return "sitPk8gJfpT4x5Gpv0aeIphFsTz4pHO38eQe4Vv1ISRuFpWVwtA23UrGgU0dQT3yqQVCPAOMzt5FIEp5bwN9n017luUya7haqEO8w9HcBOlAi5BgT1rtHiGDL33daXxzAYtgp8dH0qOgI2tjkZe7JS7fTU2QXbFPzkIBrnC8";
    }

    private function getConvertedBase64() : string {
        return "z/HGT0eecya5GTSEG6vo+7CknV9A4TSD4bt5GRs/XWApyjvOKDzhKDtfdWsheLbjmMaOH95IcfesHxqoeWj5W9F8Fv8QBGzkGxtiKMNgS7BPd6yp4xc7VlkZG7exB0z/IGzoHWifzjlYkjHZBDNoijsHYMkEiZteWr3";   
    }

    private function getConvertedCustom_32() : string {
        return ";oEaxUUVh'ro[yiE_B[a7_h?z}J;yy}d_oRU;xURa}ro7QJ_}aR'7o#}z|wG;_w;p}TUiQJ#?r[hxhG?zBw_rwEG;dyfBQ²??oRfJVUppoTaRU#²x|_a`RfiBEoG'Bf`a?#airGR?xf`yfT;i}G_?rUwiy[';UEa?iTw?r}i_|²;BrE#ayo'yf|x|B[J'#fdhr'p";
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