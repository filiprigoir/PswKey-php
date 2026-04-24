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

    public function test_input_empty_fail(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $this->expectException(InputException::class);

        $input = '';
        $output = $oneTimePad->byte($input);
    }

    public function test_context_xor_ok(): void
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
        $this->assertEmpty($status->warningMessage); //when context is given, warning is not expected
    }

    public function test_null_ok_but_warning(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text & bytes';
        $output = $oneTimePad->byte($input, 0, null); //default setting => null=without a given context

        $this->assertNotEmpty($output);

        $this->assertNotEquals(
            $input,
            $output
        );

        $status = $oneTimePad->status();
        $this->assertNotEmpty($status->warningMessage); //without context default is used and aA warning is given
    }

    public function test_different_context_ok(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text & bytes';
        $output1 = $oneTimePad->byte($input, 0, null);
        $output2 = $oneTimePad->byte($input, 0, null); //keep in mind: 0=internal auto incriment

        $this->assertNotEmpty($output1); //is StreamID 1 + default context is used
        $this->assertNotEmpty($output2); //is StreamID 2 + default context is used

         $this->assertNotEquals(
            $output1,
            $output2
        );

        $this->assertNotEquals(
            $output1,
            $output2
        );
    }

    public function test_hardcoded_id_same_results(): void
    {
        $oneTimePad = new OneTimePad(
            $this->getKeyStream('Test OneTimePad (OTP)')
        );

        $input = 'Use the function for OneTimePad with text & bytes';
        $output1 = $oneTimePad->byte($input, 1, null);
        $output2 = $oneTimePad->byte($input, 1, null);

        $this->assertEquals(
            $output1,
            $output2
        );
    }

    public function test_reverse_xor_ok(): void
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

    public function test_different_context_not_original_back(): void
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

    public function test_different_ids_not_original_back(): void 
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