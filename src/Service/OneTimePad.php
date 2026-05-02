<?php
declare(strict_types=1);

namespace PswKey\Service;

use PswKey\Core\Modifiers\ImplementationType;
use PswKey\Core\Modifiers\ShuffleProfile;
use PswKey\ErrorMessage\ClientMessage;
use PswKey\ErrorMessage\InternalMessage;
use PswKey\Exception\ConfigurationException;
use PswKey\Exception\InputException;
use PswKey\Interface\ConvertEngineInterface;
use PswKey\Interface\CustomKeyInterface;
use PswKey\Util\Mapping\Merge;
use PswKey\Util\Math\Calculation;
use PswKey\Util\Secure\MemeZero;
use PswKey\Util\Secure\OTP;
use PswKey\Validator\ValidationManagerOneTimePad;
use SensitiveParameter;

/**
 * OTP with bytes or digits
 */
class OneTimePad implements ConvertEngineInterface, CustomKeyInterface {

    protected KeyStream $_keyStream;

    private bool $_gmp = false;

    protected bool $longEndianChunk = true;

    //Implementation usage
    public string $implementation = "";

    use ValidationManagerOneTimePad;

    public function __construct(KeyStream $keyStream) {
        $this->_keyStream = $keyStream;
        $this->setAvailability();
    }

    private function setAvailability() : void {     
        $this->_gmp = function_exists('gmp_init');
    }

    /**
     * One Time Pad with digit pairs (e.g., 0123456789)
     */
    public function digit(#[SensitiveParameter] string $switchDigits, int $streamID = 0, ?string $context = null) : ?string {

        $this->resetValidator();

        $length = strlen($switchDigits);
        if($length < 2) {
            throw new InputException(
                Merge::string(InternalMessage::LENGTH_REQUIRED, 
                    ['%required%' => 'two digits']) . "/" . ClientMessage::INVALID_INPUT
            );
        }
        
        if($context === null) {
            $context = ShuffleProfile::DEFAULT_OTP_DIGITS;
            $this->setWarningMessage(
                Merge::string(InternalMessage::WARNING_EMPTY, 
                    ["%arg%" => "Third"]
                )
            );
        }
        else {
            if(mb_strlen($context, '8bit') !== 8) {
                throw new ConfigurationException(
                    InternalMessage::INVALID_LIBSODIUM_CONTEXT
                );  
            } 
        }

        $byteLen = (int)ceil(($length + 1) / log(2,10) / 8);
        $chunksize = $this->chunksize();
        $chunk = $chunksize[0];
        $chunk = $byteLen > $chunk ? $chunk : $byteLen;
        $block = (int)ceil(($chunk * 8) * log(2,10));

        $otp = '';
        $this->_keyStream->byteStream(
            function($secretBytes) use ($switchDigits, $length, $chunk, $block, &$otp) {

                $numberLeft = $length + 1;
                $pointer = 0;
                $secretIds = '';

                if($this->_gmp) {
                    //GMP
                    $this->implementation = ImplementationType::GMP;
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
                    $this->implementation = ImplementationType::BC;
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

    /**
     * One Time Pad with bytes (e.g., binary data, UTF-8 string, etc.)
     */
    public function byte(#[SensitiveParameter] string $switchBytes, int $streamID = 0, ?string $context = null) : ?string {

        $this->resetValidator();

        $length = strlen($switchBytes);
        if($length < 1) {
            throw new InputException(
                Merge::string(InternalMessage::INVALID_EMPTY, 
                    ["%arg%" => "switchBytes"]
                )
            );
        }

        if($context === null) {
            $context = ShuffleProfile::DEFAULT_OTP_BYTES;
            $this->setWarningMessage(
                Merge::string(InternalMessage::WARNING_EMPTY, 
                    ["%arg%" => "context"]
                )
            );
        }
        
        if(mb_strlen($context, '8bit') !== 8) {
            throw new ConfigurationException(
                InternalMessage::INVALID_LIBSODIUM_CONTEXT
            ); 
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
        if($this->longEndianChunk) return ShuffleProfile::ENDIAN_CHUNK_LONG;
        return ShuffleProfile::ENDIAN_CHUNK_SHORT; 
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
            $this->_clientMessage = null;
            $this->_internalMessage = null;
            $this->_status = true;
            $this->_warningMessage = null;   
            $this->implementation = "";         
        }
    }

    public function __debugInfo(): array
    {
        return ['key' => '*** hidden ***'];
    }
}