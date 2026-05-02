<?php
declare(strict_types=1);

namespace PswKey\Core;

use FFI;
use SensitiveParameter;
use PswKey\Core\Modifiers\ImplementationType;
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

    public string $implementation = ImplementationType::PHP;

    public function __construct(#[SensitiveParameter] KeyStream $keyStream) {
        $this->_keyStream = $keyStream;
        $this->setAvailability();
    }

    protected function setAvailability() : void {
        /**
         * Optional feature checks:
         * If you are certain that GMP and/or FFI are available or not,
         * you may manually set the corresponding properties to true
         */
        $ffi = ini_get('ffi.enable');
        if (class_exists('FFI') && ($ffi === '1' || $ffi === 'preload')) {
            //Setup configuration
            $file = match(PHP_OS_FAMILY) {
                'Windows' => 'shuffleindice.dll', //Windows
                'Darwin'  => match (php_uname('m')) {
                    'x86_64' => 'shuffleindice_x86_64.dylib', //old Mac with Intel CPU
                    default => 'shuffleindice.dylib', //new Mac with Apple Silicon
                },
                default   => 'shuffleindice.so',  //Linux and other Unix-systems
            };

            $allowed = __DIR__ . "/Component/FFI/Compiled/{$file}";

            //Load C library
            try {
                $this->_ffi = FFI::cdef("
                    int shuffle_indices_secure(
                        const uint8_t *rand_bytes, size_t rand_len,
                        size_t input_len,
                        size_t required_len,
                        uint8_t *out_array
                    );
                ", $allowed);
            } 
            catch (FFI\Exception) {
                $this->_ffi = null;
                $this->implementation = ImplementationType::FALLBACK;
                $this->setWarningMessage(
                    Merge::string(InternalMessage::INVALID_FFI_PATH, [
                        '%path%' => $allowed
                    ])
                );
            }
        }

        $this->_gmp = function_exists('gmp_init');
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

            //Internal role for standard base 100 and 10 convertion
            if($config['checksum'] === true && $baseLength === 10 ) {$baseLength = 100;} 

            //Shuffle within the callable
            $this->_keyStream->derivedKey(
                function($secretBytes) use ($len, $singleBytes, $flipped, $baseLength, $config, $configName) {

                    //Create buffers
                    $outBuffer = $this->_ffi->new("uint8_t[$baseLength]");

                    //Get random bytes by ID and Context.
                    $derivedLength = strlen($secretBytes);
                    $derivedBuffer = FFI::new("uint8_t[$derivedLength]");
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
                        $this->implementation = ImplementationType::FFI;

                        //Initialization
                        $this->{$config['bindingEncode']} = [];
                        $this->{$config['bindingDecode']} = $flipped;

                        //Binding with local variable by reference
                        $bindingEncode = &$this->{$config['bindingEncode']};
                        $bindingDecode = &$this->{$config['bindingDecode']};

                        for ($i=0; $i < $baseLength; $i++) { 
                            $bindingEncode[$i] = $singleBytes[$outBuffer[$i]];

                            if($config['checksum']) {
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
            include_once('Modifiers\RejectionSampling.php');

            //Specical role for standard base 100 and 10 convertion
            if($config['checksum'] === true && $baseLength === 10 ) {$baseLength = 100;} 

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
                    for ($i = $len - 1; $i > 0; $i--) {
                        $val = -1;
                        $m = $i + 1;     
                        while ($val < 0) {
                            if ($pos >= $derivedLength) $pos = 0;

                            $byte = ord($secretBytes[$pos++]);
                            if ($byte < REJECTION_SAMPLING[$m]) $val = $byte % $m;
                            $byte = 0;
                        }

                        $tmp = $indices[$i];
                        $indices[$i] = $indices[$val];
                        $indices[$val] = $tmp;
                        $tmp = 0;               
                    }

                    $index = 0;
                    if($baseLength !== $len) {
                        //Grab a new index via Fisher-Yates
                        $index = -1;
                        while ($index < 0) {
                            if ($pos >= $derivedLength) $pos = 0;

                            $pointer = ord($secretBytes[$pos++]);
                            if ($pointer < REJECTION_SAMPLING[$len]) $index = $pointer % $len;
                        }
                    }
 
                    //Snip the required base length
                    for ($i=0; $i < $baseLength; $i++) {  
                        $t = ($index + $i) % $len;
                        $bindingEncode[$i] = $singleBytes[$indices[$t]];

                        if($config['checksum']) {
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