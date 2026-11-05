<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Exception\InputException;
use PswKey\Core\Modifier\DerivationProfile;
use PswKey\Exception\ConfigurationException;
use PswKey\Service\Permutation;

class PermutationTest extends TestCase
{
    public function test_ok() : void {

        //Int number
        $originalInt = 53210;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey'); //If first arg is public, than second must be Masterkey

        //Permutation instance
        $permutation = new Permutation($keyStream);

        //Create a new unique ID (not recognizable as: 1, 2, 3, etc... but each total chaos)
        //encode:
        $permutatedId = $permutation->encode($originalInt);

        //Decode
        $originalBack = $permutation->decode($permutatedId);

        $this->assertEquals($originalInt, $originalBack);
    }

    public function test_ok_zero() : void {

        //Int number
        $originalInt = 0;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey'); //If first arg is public, than second must be Masterkey

        //Permutation instance
        $permutation = new Permutation($keyStream);

        //Create a new unique ID (not recognizable as: 1, 2, 3, etc... but each total chaos)
        //encode:
        $permutatedId = $permutation->encode($originalInt);

        //Decode
        $originalBack = $permutation->decode($permutatedId);

        $this->assertEquals($originalInt, $originalBack);
    }

    public function test_ok_max() : void {

        //Int number
        $originalInt = 999999999999999999;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey'); //If first arg is public, than second must be Masterkey

        //Permutation instance
        $permutation = new Permutation($keyStream);

        //Create a new unique ID (not recognizable as: 1, 2, 3, etc... but each total chaos)
        //encode:
        $permutatedId4 = $permutation
            ->rounds(4)
            ->maxLength(18)
            ->encode($originalInt);

        $permutatedId8 = $permutation
            ->rounds(8)
            ->encode($originalInt);

        //Decode
        $originalBack = $permutation->decode($permutatedId8);

        $this->assertEquals($originalInt, $originalBack);

        //different rounds gives a totaly different output
        $this->assertNotEquals($permutatedId4, $permutatedId8);
    }

    public function test_ok_penultimate() : void {

        //Int number
        $originalInt = 999999999999999998;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey'); //If first arg is public, than second must be Masterkey

        //Permutation instance
        $permutation = new Permutation($keyStream);
        $permutation->maxLength(18);

        //Create a new unique ID (not recognizable as: 1, 2, 3, etc... but each total chaos)
        //encode:
        $permutatedId = $permutation->encode($originalInt);

        //Decode
        $originalBack = $permutation->decode($permutatedId);

        $this->assertEquals($originalInt, $originalBack);
    }

    public function test_ok_destroy_instance() : void {

        //Int number
        $originalInt = 123456;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey');

        //Permutation instance
        $permutation = new Permutation($keyStream);

        $permutatedId = $permutation->encode($originalInt);   

        unset($permutation);

        //Permutation instance
        $permutation = new Permutation($keyStream);

        $originalBack = $permutation->decode($permutatedId);

        $this->assertEquals($originalInt, $originalBack);
    }

    public function test_fail() : void {

        //Int number
        $originalInt = 99999999999; //string of length 11

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey'); //If first arg is public, than second must be Masterkey

        //Permutation instance
        $permutation = new Permutation($keyStream);
        $permutation->maxLength(10);

        $this->expectException(InputException::class);

        $permutatedId = $permutation->encode($originalInt);
    }

    public function test_fail_too_big_int() : void {

        //Int number
        $originalInt = 999999999; 

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey');

        //Permutation instance
        $permutation = new Permutation($keyStream);
        $permutation->maxLength(10);

        $permutatedId = $permutation->encode($originalInt);

        $this->expectException(InputException::class);     
        $originalBack = $permutation->decode(99999999999);
    }

    public function test_fail_diff_config() : void {

        //Int number
        $originalInt = 123456;

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey');

        //Permutation instance
        $permutation = new Permutation($keyStream);

        $permutatedId = $permutation->encode($originalInt);   

        DerivationProfile::setContextPermutation('NewKey'); //exactly 6 bytes required, proces add the radix to 8 bytes

        unset($permutation);
        //Permutation instance
        $permutation = new Permutation($keyStream);

        $originalBack = $permutation->decode($permutatedId);

        $this->assertNotEquals($originalInt, $originalBack);
    }

    public function test_fail_too_big_maxLength() : void {

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey');

        //Permutation instance
        $permutation = new Permutation($keyStream);

        $this->expectException(ConfigurationException::class);        
        $permutation->maxLength(19);
    }

    public function test_fail_too_smal_maxLength() : void {

        //Derived stream provider
        $keyStream = new KeyStream('Your MasterKey');

        //Permutation instance
        $permutation = new Permutation($keyStream);

        $this->expectException(ConfigurationException::class);        
        $permutation->maxLength(3);
    }
}