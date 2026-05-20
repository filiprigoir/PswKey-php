<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\OneTimePad;
use PswKey\Service\PswKey;
use PswKey\Util\Secure\MemeZero;

//Base256 always returns a uniform representation
class ReadmeTest extends TestCase
{
    public function test_readme_common_usage() : void {

        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u'); 

        $keyStream = new KeyStream("Service=README.md : user=536984 : alias=VisalStudio", $key);

        $pswkey = new PswKey($keyStream);
        $randomBytes = random_bytes(100_000);

        //Conversion
        $encode = $pswkey->from(256)->to(32)->convert($randomBytes);

        //Get status 
        $status = $pswkey->status();

        if($status->valid) { //$encode !== null => is also possible

            $this->assertNotEquals(
                $encode,
                $randomBytes
            );
        
            //Decode
            $decode = $pswkey->from(32)->to(256)->convert($encode);

            //Grab new Status
            $newStatus = $pswkey->status();
            if($newStatus->valid) {

                $this->assertEquals(
                    $decode,
                    $randomBytes
                );
            }
        }
    }

    public function test_readme_advanced_usage() : void {

        try {

        $email = "info@pswkey.com";
        $password = "My_PswKeyµ2025!";

        //Derived stream provider
        $keyStream = new KeyStream(
            "Service=README.md | emailadress={$email}" //Seedphrase can be anything related to your service
        );

        $keyStream->setCustomKey($password);

        $transportkey = "";
        $passwordHash = "";
        $keyStream->byteStream(
            function($derivedPassword) use (&$transportkey, &$passwordHash, &$keyStream) {
                
                $pswKey = new PswKey($keyStream);

                $transportkey = $pswKey->from(256)->to(32)->convert($derivedPassword);
                $passwordHash = password_hash($derivedPassword, PASSWORD_DEFAULT);

                unset($pswKey);
            },
            64, 
            "MySecret"
            );

            $this->assertNotEmpty($transportkey);
            $this->assertNotEmpty($passwordHash);
        }
        finally {
            Memezero::overwriteString($password);
            unset($keyStream);   
        }
    }

    public function test_readme_validate_usage() : void {

        try {
            $email = "info@pswkey.com";

            //Incoming transport key
            $transportKey = "gUi0zrccwPdLL88IvwUwB2eMO2UtP0UvvNcITTgOv8cNl0W0o8lgPwQtoNTTlTgAA8l0P2vBl2WtAUtwANtOfIpoovpyO2"
                . "UfOlUTWMUHBNvicA2OfvzfO8UANeoz";

            $keyStream = new KeyStream("Service=README.md | emailadress={$email}");
            $pswKey = new PswKey($keyStream);

            $bytePassword = $pswKey->from(32)->to(256)->convert($transportKey);

            if($bytePassword !== null) {
                
                $dbHashed = "\$2y\$10\$0qbuEgpR/RRn4a2xj3LHu.eHH288.615tSGkBd.4kIYlyddwpdhvO";

                $status = "";
                if (password_verify($bytePassword, $dbHashed)) {
                    $status = "Password is valid!";
                } else {
                    $status = "Invalid password.";
                }

                $this->assertEquals(
                    "Password is valid!",
                    $status
                );
            }
        }
        finally {
            Memezero::overwriteString($bytePassword);
            unset($keyStream, $pswKey);   
        }
    }

    public function test_readme_otp_usage() : void {

        try {
            $originalDigits = "0931024538975689521014785";

            $date = new DateTime();
            $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

            //Derived stream provider
            $keyStream = new KeyStream("Service=README.md : user=5236947 : alias=VisalStudio", $key);

            $oneTimePad = new OneTimePad($keyStream);
            $encode = $oneTimePad->digit($originalDigits, 5236947, "MyDigits");

            $this->assertNotEquals(
                $encode,
                $originalDigits
            );

            $status = $oneTimePad->status();
            if($status->valid) {

                $decode = $oneTimePad->digit($encode, 5236947, "MyDigits");

                $this->assertEquals(
                    $decode,
                    $originalDigits
                );
            }
        }       
        finally {
            Memezero::overwriteString($originalDigits);
            unset($keyStream, $oneTimePad); 
        }
    }
}