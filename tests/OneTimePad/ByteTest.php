<?php declare(strict_types=1);

namespace Tests\Unit\PswKey;

use DateTime;
use PHPUnit\Framework\TestCase;
use PswKey\Service\KeyStream;
use PswKey\Service\OneTimePad;
use PswKey\Exception\InputException;

class ByteTest extends TestCase
{
    private function getKeyStream(string $seedPhrase) : KeyStream {
        $date = new DateTime();
        $key = \strtotime($date->format('Y-m-d H:i:s')) . $date->format('u');
        return new KeyStream($seedPhrase, $key);
    }

    public function testByte_empty(): void
    {
        //every request and every test will generate different secret ids
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $this->expectException(InputException::class);

        $input = '';
        $output = $oneTimePad->byte($input);
    }

    public function testByte_context_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text and bytes';
        $output = $oneTimePad->byte($input, 0, "BytesOTP"); //default setting => 0=auto incriment

        $this->assertNotEmpty($output);

        $this->assertNotEquals(
            $input,
            $output
        );

        $status = $oneTimePad->status();
        $this->assertEmpty($status->warning); //context is given so no warning and default use
    }

    public function testByte_null_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text & bytes';
        $output = $oneTimePad->byte($input, 0, null); //default setting => null=without given context

        $this->assertNotEmpty($output);

        $this->assertNotEquals(
            $input,
            $output
        );

        $status = $oneTimePad->status();
        $this->assertNotEmpty($status->warning); //without context default is used. A warning is given
    }

    public function testByte_different_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text & bytes';
        $output1 = $oneTimePad->byte($input, 0, null);
        $output2 = $oneTimePad->byte($input, 0, null); //keep in mind: 0=internal auto incriment

        $this->assertNotEmpty($output1);
        $this->assertNotEmpty($output2);

        $this->assertNotEquals(
            $output1,
            $output2
        );
    }

    public function testByte_same_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text & bytes';
        $output1 = $oneTimePad->byte($input, 1, null);
        $output2 = $oneTimePad->byte($input, 1, null); //attention: any same ID (int) is working

        $this->assertEquals(
            $output1,
            $output2
        );
    }

    public function testByte_reverse_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text and bytes';
        $encode = $oneTimePad->byte($input, 5312051897, "BytesOTP");

        $this->assertNotEmpty($encode);

        $decode = $oneTimePad->byte($encode, 5312051897, "BytesOTP");

        $this->assertEquals(
            $input,
            $decode
        );
    }

    public function testByte_reverse_contextfail_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text and bytes';
        $encode = $oneTimePad->byte($input, 5312051897, "BytesOTP");

        $this->assertNotEmpty($encode);

        $decode = $oneTimePad->byte($encode, 5312051897, "MySwitch");

        $this->assertNotEquals(
            $input,
            $decode
        );
    }

    public function testByte_reverse_idfail_xor(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text and bytes';
        $encode = $oneTimePad->byte($input, 5312051897, "BytesOTP");

        $this->assertNotEmpty($encode);

        $decode = $oneTimePad->byte($encode, 5312051896, "BytesOTP");

        $this->assertNotEquals(
            $input,
            $decode
        );
    }
}