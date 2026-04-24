<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\OneTimePad;
use PswKey\Exception\InputException;

class DigitTest extends TestCase
{
    private function getKeyStream(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    private function getDigits() : string {
        //610 random digits in string
        return '570417759969558280306741360185654482132421204132876178709721743832046592394771642905945187497632045939206449593'
            . '4193525932287415577563869103314624465875348131509413879740232979912824244315557791826573780792091598438962777695'
            . '5002349395450504829772536374864392362604722061759411404833377864333134609322026061613353119540633431016393408643'
            . '8277057152371535851531574685758608914740728572437584418129936751698931535487889531754923630016074639849888237813'
            . '5407053578507530764386481423886531641953951117768264007584273493519624206652783058761370027418974779443936114650'
            . '077134404239519071626161079423697016132120307704048';
    }

    public function test_input_empty(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $this->expectException(InputException::class);

        $input = '';
        $output = $oneTimePad->digit($input);
    }

    public function test_input_too_short(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $this->expectException(InputException::class);

        $input = '1';
        $output = $oneTimePad->digit($input);
    }

    public function test_php_gmp_ok(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        //setting 
        $oneTimePad->longEndianChunk(true); //default is true

        $input = $this->getDigits();
        
        if(function_exists('gmp_init')) {
    
            //Section only if GMP state is enable
            $oneTimePad->gmpEnable(true); //hardcoded so GMP enabled for sure
            $output1 = $oneTimePad->digit($input, 1, "DigitOTP");

            $this->assertEquals(
                'GMP',
                $oneTimePad->implementation
            );
            
            $oneTimePad->gmpEnable(false); //hardcoded so GMP disabled for sure
            $output2 = $oneTimePad->digit($input, 1, "DigitOTP"); 
            
            $this->assertEquals(
                'BC',
                $oneTimePad->implementation
            );

            $this->assertNotEmpty($output2);

            $this->assertEquals(
                $output1,
                $output2
            );
        }
        else {
            $oneTimePad->gmpEnable(false);
            $output = $oneTimePad->digit($input, 1, "DigitOTP");  

            $this->assertNotEmpty($output);
        }
    }

    public function test_php_gmp_ok_but_chunk_different(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        //setting 
        $oneTimePad->longEndianChunk(true); //default is true | true=169 bytes chunk, false=22 bytes chunk

        $input = $this->getDigits();
        
        if(function_exists('gmp_init')) {
    
            //Section only if GMP status is enable
            $oneTimePad->gmpEnable(true);
            $output1 = $oneTimePad->digit($input, 1, "DigitOTP");

            $this->assertEquals(
                'GMP',
                $oneTimePad->implementation
            );            
            
            $oneTimePad->gmpEnable(false); //disble is same result as long as longEndianChunk is true
            $oneTimePad->longEndianChunk(false); //set to false so result is different, because chunk size is different
            $output2 = $oneTimePad->digit($input, 1, "DigitOTP");  

            $this->assertEquals(
                'BC',
                $oneTimePad->implementation
            );
            
            $this->assertNotEquals(
                $output1,
                $output2
            );
        }
        else {
            $oneTimePad->gmpEnable(false);
            $output = $oneTimePad->digit($input, 1, "DigitOTP");  

            $this->assertNotEmpty($output);
        }
    }

    public function test_reverse_ok(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = $this->getDigits();
        $encode = $oneTimePad->digit($input); //use internal incriment
        $decode = $oneTimePad->digit($encode, 1); //use manual 1 because internal counter is 2
        
        $this->assertEquals(
            $input,
            $decode
        );
    }

    public function test_different_ids_not_original_input(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = $this->getDigits();
        $encode = $oneTimePad->digit($input, 1, null); //set ID 1
        $decode = $oneTimePad->digit($encode, 2, null); //set ID 2
        
        $this->assertNotEquals(
            $input,
            $decode
        );
    }

    public function test_different_context_not_original_input(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = $this->getDigits();
        $encode = $oneTimePad->digit($input, 53214587, "MyDigits"); //set big ID
        $decode = $oneTimePad->digit($encode, 53214587, "DigitOTP"); //set same big ID, but different context
        
        //not matching ID's failes
        $this->assertNotEquals(
            $input,
            $decode
        );

        $this->assertEquals(
            610,
            strlen($encode)
        );
    }
}