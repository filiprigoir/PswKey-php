<?php
declare(strict_types=1);

namespace PswKey\Core;

use Exception;
use FFI;
use SensitiveParameter;
use PswKey\Core\Component\FFI\InstanceFFI;
use PswKey\Core\Modifier\ImplementationType;
use PswKey\ErrorMessage\InternalMessage;
use PswKey\Interface\CustomKeyInterface;
use PswKey\Service\KeyStream;
use PswKey\Util\Mapping\Merge;
use PswKey\Util\Math\Calculation;
use PswKey\Util\Secure\MemeZero;

/**
 * Shuffles an alphabet using indices between 1 and 255
 */
abstract class ShuffleChars extends BaseConvert implements CustomKeyInterface {

    private KeyStream $_keyStream;
    
    //standard dissabled & blocked by shared servers
    protected ?FFI $_ffi = null; 

    public function __construct(#[SensitiveParameter] KeyStream $keyStream) {
        $this->_keyStream = $keyStream;
        $this->setAvailability();
    }

    protected function setAvailability() : void {
        try {
            $this->_ffi = InstanceFFI::scope();
            if($this->_ffi) $this->implementation = ImplementationType::FFI;
        }
        catch(Exception $e) {
            $this->_ffi = null;
            $this->implementation = ImplementationType::FALLBACK;
            $this->setWarningMessage(
                Merge::string(InternalMessage::INVALID_FFI_PATH, [
                    '%path%' => $e->getMessage()
                ])
            );
        }
        finally {
            $this->_gmp = function_exists('gmp_init');
            Calculation::$initSize = PHP_INT_SIZE === 8 ? 7 : 3;            
        }
    }
    
    //Shuffle with C (FFI) if enable
    final protected function shuffleFFI(array|string $singleBytes, int $baseLength, string $configName, bool $isCustom = false) : void {

        $config = $this->{$configName};
        if(empty($this->{$config['bindingStr']})) {

            $isStr = is_string($singleBytes);
            if($isStr) {
                $len = strlen($singleBytes);
                $flipped = array_flip(str_split($singleBytes));
            }
            else {
                $len = count($singleBytes);
                $flipped = array_flip($singleBytes);
            }

            //Internal rule for standard base 100 and 10 convertion
            if($config['isCanonical'] === true && $baseLength === 10 ) {$baseLength = 100;} 

            //Shuffle within the callable
            $this->_keyStream->derivedKey(
                function($secretBytes) use ($len, $singleBytes, $flipped, $baseLength, $config, $configName) {

                    //Create buffers
                    $outBuffer = $this->_ffi->new("uint8_t[$baseLength]");

                    //Get random bytes by ID and Context.
                    $derivedLength = strlen($secretBytes);
                    $derivedBuffer = $this->_ffi->new("uint8_t[$derivedLength]");
                    FFI::memcpy($derivedBuffer, $secretBytes, $derivedLength);

                    //Call C function 
                    $method_C_function = "shuffle_indices_secure";
                    $c = $this->_ffi->{$method_C_function}(
                        $derivedBuffer, $derivedLength,
                        $len,
                        $baseLength,
                        $outBuffer
                    );

                    FFI::memset($derivedBuffer, 0, $derivedLength);

                    if($c === 0) {
                        //Initialization
                        $this->{$config['bindingEncode']} = [];
                        $this->{$config['bindingDecode']} = $flipped;

                        //Binding with local variable by reference
                        $bindingEncode = &$this->{$config['bindingEncode']};
                        $bindingDecode = &$this->{$config['bindingDecode']};

                        for ($i=0; $i < $baseLength; $i++) { 
                            $bindingEncode[$i] = $singleBytes[$outBuffer[$i]];

                            if($config['isCanonical']) {
                                $bindingDecode[$bindingEncode[$i]] = sprintf("%02d", $i);
                            }
                            else {
                               $bindingDecode[$bindingEncode[$i]] = $i; 
                            }
                        }

                        $this->{$config['bindingStr']} = implode('', $bindingEncode);
                        FFI::memset($outBuffer, 0, $baseLength);
                    }
                    else {
                        $this->shufflePHP($singleBytes, $baseLength, $configName);
                        $this->implementation = ImplementationType::FALLBACK;
                        $this->setWarningMessage(
                            Merge::string(InternalMessage::WARNING_FFI_FAILED, [
                                '%base%' => $config['base']
                            ])
                        );
                    }
                },
                (int)Calculation::getFactor($len),
                $config['context'],
                $isCustom
            );
        }
    }

    //Shuffle with PHP if C (FFI) is disable
    final protected function shufflePHP(array $singleBytes, int $baseLength, string $configName, bool $isCustom = false) : void {

        $config = $this->{$configName};
        if(empty($this->{$config['bindingStr']})) {

            $len = count($singleBytes);

            //Table rejection sampling
            include_once(__DIR__ . '/Modifier/RejectionSampling.php');

            //Specical rule for standard base 100 and 10 convertion
            if($config['isCanonical'] === true && $baseLength === 10 ) {$baseLength = 100;} 

            //Shuffle within the callable
            $this->_keyStream->derivedKey(
                function($secretBytes) use ($len, $singleBytes, $baseLength, $config) {
                                
                    //Random dervided bytes
                    $derivedLength = strlen($secretBytes);

                    //Initialization
                    $indices = range(0, $len - 1);
                    $this->{$config['bindingEncode']} = [];
                    $this->{$config['bindingDecode']} = array_flip($singleBytes);

                    //Binding with local variable by reference
                    $bindingEncode = &$this->{$config['bindingEncode']};
                    $bindingDecode = &$this->{$config['bindingDecode']};

                    //Shuffle the indices
                    $pos = 0;     
                    for($i = $len - 1; $i > 0; $i--) {
                        $val = -1;
                        $m = $i + 1;     
                        while($val < 0) {
                            if($pos >= $derivedLength) $pos = 0;

                            $byte = ord($secretBytes[$pos++]);
                            if($byte < REJECTION_SAMPLING[$m]) $val = $byte % $m;
                            $byte = 0;
                        }

                        $tmp = $indices[$i];
                        $indices[$i] = $indices[$val];
                        $indices[$val] = $tmp;
                        $tmp = 0;               
                    }

                    //Snip the required base length
                    for($i=0; $i < $baseLength; $i++) {  
                        $bindingEncode[$i] = $singleBytes[$indices[$i]];

                        if($config['isCanonical']) {
                            $bindingDecode[$bindingEncode[$i]] = sprintf("%02d", $i);
                        }
                        else {
                            $bindingDecode[$bindingEncode[$i]] = $i;
                        }                        
                    }

                    $this->{$config['bindingStr']} = implode('', $bindingEncode);
                    MemeZero::overwriteArray($indices);
                },
                (int)Calculation::getFactor($len),
                $config['context'],
                $isCustom,  
            );
        }
    }

    final protected function clearShuffleChars() : void {
        if($this->_ffi !== null) unset($this->_ffi);
    }

    public function setCustomKey(string $seedPhrase) : self {
        $this->_keyStream->setCustomKey($seedPhrase);
        return $this;
    }

    public function resetCustomKey() : self {
        $this->_keyStream->resetCustomKey();
        return $this;
    }

    public function enabledFFI() : bool  {
        return !empty($this->_ffi);
    }
}