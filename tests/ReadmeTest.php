<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\PswKey;

//Base256 always returns a uniform representation
class ReadmeTest extends TestCase
{
    
public function test_readme_common_usage() : void {

    //key change in time
    $date = new DateTime();
    $key = $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u'); 

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
}