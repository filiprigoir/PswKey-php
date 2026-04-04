<?php
declare(strict_types=1);

namespace PswKey\Service;

use PswKey\Exception\InputException;
use PswKey\Interface\ConvertEngineInterface;
use PswKey\Interface\CustomKeyInterface;
use PswKey\Util\Math\Calculation;
use PswKey\Util\Secure\MemeZero;
use PswKey\Util\Secure\OTP;
use PswKey\Validator\InputHandlerOneTimePad;
use SensitiveParameter;

/**
 * OTP with bytes or digits
 */
class OneTimePad implements ConvertEngineInterface, CustomKeyInterface {

    //Secret derive Key with callable
    protected KeyStream $_keyStream;

    //Availibility of functions
    private bool $_gmp = false; //standard dissabled

    //Set to false, chunks are limited to 22 bytes; set to true, chunks are up to 169 bytes as a big-endian integer.
    protected bool $longEndianChunk = true;

    //Info
    public string $usage = "";

    use InputHandlerOneTimePad;

    public function __construct(KeyStream $keyStream) {
        $this->_keyStream = $keyStream;
        $this->setAvailability();
    }

    private function setAvailability() : void {     
        $this->_gmp = function_exists('gmp_init');
    }

    public function digit(#[SensitiveParameter] string $switchDigits, int $streamID = 0, ?string $context = null) : ?string {

        $this->resetValidator();

        $length = strlen($switchDigits);
        if($length < 2) {
            throw new InputException("First argument must have a length of at least 2 digits");
        }
        
        if($context === null) {
            $context = 'derive_d';
            $this->setWarningMessage("Third argument 'context' is empty in digit(): default 'derive_d' is used");
        }
        else {
            if(mb_strlen($context, '8bit') !== 8) {
                $this->setWarningMessage("Third argument 'context' is not 8-bit in digit(); default 'derive_d' is used");
                $context = 'derive_d';
            } 
        }

        //Digits length of bytes
        $byteLen = (int)ceil(($length + 1) / log(2,10) / 8);
        //Chunk
        $chunksize = $this->chunksize();
        $chunk = $chunksize[0];
        $chunk = $byteLen > $chunk ? $chunk : $byteLen;
        //Block for padding zeros
        $block = (int)ceil(($chunk * 8) * log(2,10));

        $otp = '';
        $this->_keyStream->byteStream(
            function($secretBytes) use ($switchDigits, $length, $chunk, $block, &$otp) {

                $numberLeft = $length + 1;
                $pointer = 0;
                $secretIds = '';

                if($this->_gmp) {
                    //GMP
                    $this->usage = "GMP";
                    do {
                        $digs = gmp_strval(
                            gmp_import(substr($secretBytes, $pointer, $chunk)),
                            10
                        );

                        $pointer += $chunk; 
                        $numberLeft -= $block;
                        $secretIds .= \str_pad($digs, $block, '0', STR_PAD_LEFT);

                    } while($numberLeft > 0);
                }
                else {
                    //PHP
                    $this->usage = "BC";
                    do {
                        $digs = Calculation::bytesToDec(
                            substr($secretBytes, $pointer, $chunk),
                            10
                        );

                        $pointer += $chunk; 
                        $numberLeft -= $block;
                        $secretIds .= \str_pad($digs, $block, '0', STR_PAD_LEFT);

                    } while($numberLeft > 0);
                }

                $tmp = substr($secretIds, 1, null);
                $otp = OTP::digits(
                    $switchDigits,
                    $tmp
                );

                MemeZero::overwriteBulkString([$secretIds, $tmp]);
            },
            $byteLen,
            $context, 
            $streamID,
        );

        return $otp;
    }

    public function byte(#[SensitiveParameter] string $switchBytes, int $streamID = 0, ?string $context = null) : ?string {

        $this->resetValidator();

        $length = strlen($switchBytes);
        if($length < 1) {
            throw new InputException("First argument switchBytes may not be empty");
        }

        if($context === null) {
            $context = 'random_b';
            $this->setWarningMessage("Third argument 'context' in byte() is empty: default 'random_d' is used");
        }
        
        if(mb_strlen($context, '8bit') !== 8) {
            $context = 'random_b';
            $this->setWarningMessage("Third argument 'context' is not 8-bit in byte(); default 'random_d' is used");
        } 
      
        $otp = '';
        $this->_keyStream->byteStream( 
            function($secretBytes) use ($switchBytes, &$otp) {
                $otp = OTP::bytes(
                    $secretBytes,
                    $switchBytes
                );    
            },
            $length,
            $context, 
            $streamID,
        );
        
        return $otp;
    }

    public function chunksize() : array {
        if($this->longEndianChunk) return [169, 407];
        return [22, 53];
    }

    public function longEndianChunk(bool $longEndianChunk) : self {
        $this->longEndianChunk = $longEndianChunk;
        return $this;
    }

    public function gmpEnable(bool $useGMP) : self {
        $this->_gmp = $useGMP;
        return $this;
    }

    public function setCustomKey(string $seedPhrase) : self {
        $this->_keyStream->setCustomKey($seedPhrase);
        return $this;
    }

    public function resetCustomKey() : self {
        $this->_keyStream->resetCustomKey();
        return $this;
    }

    /** 
     * Overide @return Void 
     */
    public function resetValidator() : void {
        if(!$this->_status) {
            $this->_customerMessage = null;
            $this->_systemMessage = null;
            $this->_status = true;
            $this->_warningMessage = null;   
            $this->usage = "";         
        }
    }

    public function __debugInfo(): array
    {
        return ['key' => '*** hidden ***'];
    }
}