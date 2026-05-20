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

        //key change in time
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u'); 

        //Derived Provider
        $keyStream = new KeyStream("Service=README.md : user=536984 : alias=VisalStudio", $key);

        //Deterministic converter
        $pswkey = new PswKey($keyStream);

        //Grab 100,000 random bytes
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
            else {
                //Message for users and client(public)
                echo $status->clientMessage;
            }
        }
        else {
            //Message for devs/logs (protected)
            echo $status->internalMessage;
        }
    }

    public function test_readme_advanced_usage() : void {

        try {

        //User credentials
        $email = "info@pswkey.com";
        $password = "My_PswKeyµ2025!";

        //Derived stream provider
        $keyStream = new KeyStream(
            "Service=README.md | emailadress={$email}" //Seedphrase can be anything related to your service
        );

        //Attach password as custom key
        $keyStream->setCustomKey($password);

        //Generated outputs
        $transportkey = "";
        $passwordHash = "";
        $keyStream->byteStream(
            function($derivedPassword) use (&$transportkey, &$passwordHash, &$keyStream) {
                
                $pswKey = new PswKey($keyStream);

                //Derived password => transport-safe Base32 representation
                $transportkey = $pswKey->from(256)->to(32)->convert($derivedPassword);

                //Derived password => password hash for database storage
                $passwordHash = password_hash($derivedPassword, PASSWORD_DEFAULT);

                unset($pswKey);
            },
            64, //Derived password length
            "MySecret" //Your service-specific derivation context (most be 8 bytes)
            );

            $this->assertNotEmpty($transportkey);
            $this->assertNotEmpty($passwordHash);

            //To validate:
            //regenerate the "derived password" from the incoming password
            //and compare it with the stored database hash

            //Example password hash:
            //$2y$10$0qbuEgpR/RRn4a2xj3LHu.eHH288.615tSGkBd.4kIYlyddwpdhvO

            //Optional "transport key" representation:
            //(cookies, transport identifiers, login flows, etc.)

            //Example transport key:
            //gUi0zrccwPdLL88IvwUwB2eMO2UtP0UvvNcITTgOv8cNl0W0o8lgPwQtoNTTlTgAA8l0P2vBl2WtAUtwANtOfIpoovpyO2
            //UfOlUTWMUHBNvicA2OfvzfO8UANeoz
        }
        finally {
            //Clear sensitive data
            Memezero::overwriteString($password);
            unset($keyStream);   
        }
    }

    public function test_readme_validate_usage() : void {

        try {
            //User credentials
            $email = "info@pswkey.com";

            //Incoming transport key
            $transportKey = "gUi0zrccwPdLL88IvwUwB2eMO2UtP0UvvNcITTgOv8cNl0W0o8lgPwQtoNTTlTgAA8l0P2vBl2WtAUtwANtOfIpoovpyO2"
                . "UfOlUTWMUHBNvicA2OfvzfO8UANeoz";

            //Derived stream provider
            $keyStream = new KeyStream("Service=README.md | emailadress={$email}");

            $pswKey = new PswKey($keyStream);

            //Decoded transport key into derived password bytes
            $bytePassword = $pswKey->from(32)->to(256)->convert($transportKey);

            //Validate transport key conversion
            if($bytePassword !== null) {
                
                //Retrieve stored password hash only after successful transport-key validation
                $dbHashed = "\$2y\$10\$0qbuEgpR/RRn4a2xj3LHu.eHH288.615tSGkBd.4kIYlyddwpdhvO";

                //Compare derived password against stored hash
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
            //Clear sensitive data
            Memezero::overwriteString($bytePassword);
            unset($keyStream, $pswKey);   
        }
    }

    public function test_readme_otp_usage() : void {

        try {
            //Digit number
            $originalDigits = "0931024538975689521014785";

            //Time-based key
            $date = new DateTime();
            $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');

            //Derived stream provider
            $keyStream = new KeyStream("Service=README.md : user=5236947 : alias=VisalStudio", $key);

            //OTP instance
            $oneTimePad = new OneTimePad($keyStream);

            //Using any numeric identifier and 8-byte derivation context
            $encode = $oneTimePad->digit($originalDigits, 5236947, "MyDigits");

            $this->assertNotEquals(
                $encode,
                $originalDigits
            );

            $status = $oneTimePad->status();
            if($status->valid) {

                //Decode into orginal digits ith same numeric identifier and 8-byte derivation context
                $decode = $oneTimePad->digit($encode, 5236947, "MyDigits");

                $this->assertEquals(
                    $decode,
                    $originalDigits
                );
            }
            else {
                echo $status->internalMessage;
            }
        }       
        finally {
            //Clear sensitive data
            Memezero::overwriteString($originalDigits);
            unset($keyStream, $oneTimePad); 
        }
    }
}