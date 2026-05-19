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

    public function test_readme_usecase_transportKey() : void {

        try {

            //API receives a password
            $email = "info@pswkey.com";
            $password = "My_PswKeyµ2025!";

            //Make a Derival Provider
            $keyStream = new KeyStream("Service=README.md | emailadress={$email}");
            $keyStream->setCustomKey($password);

            //Get stronger Byte Password
            $passkey = "";
            $passwordHash = "";
            $keyStream->byteStream(
                function($streamedPassword) use (&$passkey, &$passwordHash, &$keyStream) {
                    
                    //Instande PswKey
                    $pswKey = new PswKey($keyStream);

                    //Passkey/transportkey
                    $passkey = $pswKey->from(256)->to(32)->convert($streamedPassword);

                    //DB password of that user
                    $passwordHash = password_hash($streamedPassword, PASSWORD_DEFAULT);

                    unset($pswKey);
                },
                64,
                "MySecret" //your service context
                );

                $this->assertNotEmpty($passkey);
                $this->assertNotEmpty($passwordHash);

                //$passkey contains 64 byte password stored as transportkey
                //gUi0zrccwPdLL88IvwUwB2eMO2UtP0UvvNcITTgOv8cNl0W0o8lgPwQtoNTTlTgAA8l0P2vBl2WtAUtwANtOfIpoovpyO2UfOlUTWMUHBNvicA2OfvzfO8UANeoz
                //$passwordHash contains a new safety password_hash to store in DB
                //$2y$10$RGbITslA7qBNeiJ8/Zk3A.t7sOXpOLHqTgqT5.mtSmrGpIL9c75jG
        }
        finally {
            //Clear
            Memezero::overwriteString($password);
            unset($keyStream);   
        }
    }

    public function test_readme_validate_transportKey() : void {

        try {

            //API receives a transport key/passkey
            $email = "info@pswkey.com";
            $transportKey = "gUi0zrccwPdLL88IvwUwB2eMO2UtP0UvvNcITTgOv8cNl0W0o8lgPwQtoNTTlTgAA8l0P2vBl2WtAUtwANtOfIpoovpyO2UfOlUTWMUHBNvicA2OfvzfO8UANeoz";

            //Stored in DB
            $dbHashed = "\$2y\$10\$RGbITslA7qBNeiJ8/Zk3A.t7sOXpOLHqTgqT5.mtSmrGpIL9c75jG";

            //Make a Derival Provider
            $keyStream = new KeyStream("Service=README.md | emailadress={$email}");

            $pswKey = new PswKey($keyStream);

            //Compare with hash from DB
            $strongPassword = $pswKey->from(32)->to(256)->convert($transportKey);

            $status = "";
            if ($strongPassword !== null && password_verify($strongPassword, $dbHashed)) {
                $status = 'Password is valid!';
            } else {
                $status = 'Invalid password.';
            }

            $this->assertEquals(
                'Password is valid!',
                $status
            );
        }
        finally {
            //Clear
            Memezero::overwriteString($strongPassword);
            unset($keyStream, $PswKey);   
        }
    }
}