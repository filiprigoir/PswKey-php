<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\OneTimePad;
use PswKey\Service\Permutation;
use PswKey\Service\PswKey;
use PswKey\Util\Secure\MemeZero;

/**
 * Code defined in README.md
 */
class ReadmeTest extends TestCase
{
    //Base256 always returns a uniform representation
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
                $passwordHash = password_hash(base64_encode($derivedPassword), PASSWORD_DEFAULT);

                unset($pswKey);
            },
            32, 
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
            $transportKey = "nVffjAflteuyIIZDKsyfzpfBPPofb8yevVoOZsZspbOgAz7BDevBflsPD8tOpes";

            $keyStream = new KeyStream("Service=README.md | emailadress={$email}");
            $pswKey = new PswKey($keyStream);

            $bytePassword = $pswKey->from(32)->to(256)->convert($transportKey);

            if($bytePassword !== null) {
                
                $dbHashed = "\$2y\$12\$vMMA/l7JKMXvgeJavf5WAuvGMdFhfypltZfAyNyNyIyku1SDUf4zq";

                $status = "";
                if (password_verify(base64_encode($bytePassword), $dbHashed)) {
                    $status = "Password is valid!";
                } else {
                    $status = "Invalid password.";
                }

                $this->assertEquals(
                    "Password is valid!",
                    $status
                );
            }
            else {
                //Standard fail test messages (make sure you got message when it goed wrong)
                $this->assertEquals(
                    "Password is valid!",
                    "Password is not the same"
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

    public function test_permutation() : void {
        //Int number
        $originalInt = 53210;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey'); //If first arg is public, than second must be Masterkey

        //Permutation instance (range is default 8 => 0 to 99999999)
        $permutation = new Permutation($keyStream);

        //Create a new unique ID (not recognizable as: 1, 2, 3, etc... but each total chaos)
        //encode:
        $permutedId = $permutation->encode($originalInt);

        $this->assertNotEquals(
            $permutedId,
            $originalInt
        );

        //Decode
        $originalBack = $permutation->decode($permutedId);

        $this->assertEquals(
            $originalBack,
            $originalInt
        );
    }
}