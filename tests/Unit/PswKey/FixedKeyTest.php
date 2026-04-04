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
        return "4268513814125533843126150109739900138483816526509820840147020365864803053467373476358147379515239173056156725039968947467965551971837519384564135499785990765200362340869875802266895425716435027464730775263945202677347776463622034329152090122406";
    }

    private function getConvertedBase100() : string {
        return "5bTow]!^aQ³8PYnzDla=µ}³#~*aP|VI}@WI{f3Zf:sµ|Zt8´,n{'hN#7q&|.u}!_g=r_oAClRzJdj:2D+´U@~r\$LK&RGgCsV9Cn%r³7A*³BfB:.+LI¨k8*j]\(";
    }

    private function getConvertedBase32() : string {
        return "UW7pbss2AKNWcqt7gRcpkgAeH3aUqq3SgWisUbsip3NWkEag3piKkWD3H0jGUgjUx3ostEaDeNcAbAGeHRjgNj7GUSqMREweeWiMa2sxxWopisDwb0gphiMtR7WGKRMhpeDptNGiebMhqMoUt3GgeNsjtqcKUs7petojeN3tg0wURN7DpqWKqM0b0RcaKDMSANKA";
    }

    private function getConvertedBase58() : string {
        return "PG76RwHdSLpJac576ikdgvm6urMKgvXLTuPFUyoaWW15aU7E8CrvhckxYXXptyKqzuQGiauB1qmAr1u5E2NYFC532DQeP5JZz38EqGA5kgnZNyYNiK2mYb4CFtBGFpgG39ZLQRM8aCf7X7nzZuzAcny1i9JCoNJ2qTKqRPiZ";
    }

    private function getConvertedBase62() : string {
        return "vBazEtGmy0Z4XPk0D76Oi0USvZn40cKQtOIO4VDbiYrhS0lVRas8QeNkGe71IZQpxIVJzsK3naPSiM0P9Rw2o7bAdhep6AU6xMKtR2cuLKdsBPLGZbNacBk5HQQ16FXnsTaG0t1c7xKGi8afEqOAmYAyZe8IF9SznEiLNoJt";
    }

    private function getConvertedBase64() : string {
        return "ikyBV9AAgNl7BVMRBqQUzT82FjsbxVMSxZw7BCEkmtbhNoQXrSicrSwIDtEcAvZo0nlXys7GgIAEypeUAto7ts3f3Qf+OBi2BpwPrnaYMTOdDqNhxpgTj52JBTApO9ikGBiUytPIio5/2oyJOSaUPoEy/n2RPJwAtuK";
    }

    private function getConvertedCustom_32() : string {
        return "KA´eT££a/BgAi5&´r³ie{r/bxI§K55I*rAP£KT£PeIgA{7§rIePB{AfIxRDlKrDK.I@£&7§fbgi/T/lbx³DrgD´lK*5V³7WbbAPV§a£..A@eP£fWTRreyPV&³´AlB³Vyebfe&glPbTVy5V@K&Ilrbg£D&5iBK£´eb&@DbgI&rRWK³g´fe5AB5VRTR³i§BfV*/gB/";
    }

    public function test_from_base100(): void
    {
        $text = $this->getText();
        $pswkey = $this->instancePswKey();

        //Create single bytes first, there are int over 127
        $base100 = Transcode::getISO($this->getConvertedBase100());

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //Create single bytes first, there are int over 127
        $base10 = Transcode::getISO($this->getConvertedBase10());

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //Create single bytes first, there are int over 127
        $base32 = $this->getConvertedBase32();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //Create single bytes first, there are int over 127
        $base58 = $this->getConvertedBase58();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //Create single bytes first, there are int over 127
        $base62 = $this->getConvertedBase62();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //Create single bytes first, there are int over 127
        $base64 = $this->getConvertedBase64();

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
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

        //Create single bytes first, there are int over 127
        $custom32 = Transcode::getISO($this->getConvertedCustom_32());

        //Note: This service only accepts single bytes. It does not work with multiple bytes with prefixes in string.
        $decode = $pswkey
            ->customFrom(initArray::_base100(), 32, true) //shuffles 100 char but take only 32 char with Fisher-Yates
            ->to(256)
            ->convert($custom32);

        $this->assertEquals(
            $text,
            $decode
        );
    }
}