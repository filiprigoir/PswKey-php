<?php
declare(strict_types=1);

namespace PswKey\Core;

use FFI;
use SensitiveParameter;
use PswKey\Interface\CustomKeyInterface;
use PswKey\Service\KeyStream;
use PswKey\Util\Secure\MemeZero;

/**
 * Shuffles an alphabet using indices between 1 and 255
 */
abstract class ShuffleChars extends BaseConvert implements CustomKeyInterface {

    //Object Derive Key
    private KeyStream $_keyStream;

    //Check availability GMP
    protected ?FFI $_ffi = null; //standard dissabled & blocked by shared servers

    //Inform about the shuffle
    public string $usage = 'PHP';

    public function __construct(#[SensitiveParameter] KeyStream $keyStream) {
        $this->_keyStream = $keyStream;
        $this->setAvailability();
    }

    /**** Protected Methods ****/

    protected function setAvailability() : void {
        /**
         * Optional feature checks:
         * If you are certain that GMP, Libsodium, and/or FFI are available,
         * you may comment out this section and manually set the corresponding
         * properties to true (FFI set equal to using extension or disable)
         */
        $ffi = ini_get('ffi.enable'); //set enable or disable
        if (class_exists('FFI') && ($ffi === '1' || $ffi === 'preload')) {
            //Setup configuration
            $extension = match(PHP_OS_FAMILY) {
                'Windows' => 'dll',   //Win
                'Darwin'  => 'dylib', // macOS
                default   => 'so',    // Linux en other Unix-systems
            };

            //Allowed Path
            $allowed = __DIR__ . "/Component/FFI/Compiled/shuffleindice.{$extension}";

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
                $this->usage = "PHP_FALLBACK";
                $this->setWarningMessage("FFI failed to load: $allowed");
            }
        }

        $this->_gmp = function_exists('gmp_init');
    }
    
    //Shuffle with C (FFI) if enable
    final protected function shuffleFFI(array|string $singleBytes, int $baseLength, string $configName, bool $isCustom = false) : void {

        //Generic properties
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

            //Specical role for standard base 100 and 10 convertion
            if($config['checksum'] === true && $baseLength === 10 ) {$baseLength = 100;} 

            //Shuffle within the callable
            $this->_keyStream->derivedKey(
                function($secretBytes) use ($len, $singleBytes, $flipped, $baseLength, $config, $configName) {

                    //Create buffers
                    $outBuffer = $this->_ffi->new("uint8_t[$baseLength]");

                    //Get random bytes by ID and Context.
                    $randLength = strlen($secretBytes);
                    $randBuffer = FFI::new("uint8_t[$randLength]");
                    FFI::memcpy($randBuffer, $secretBytes, $randLength);

                    //Call C function 
                    $method_C_function = "shuffle_indices_secure";
                    $c = $this->_ffi->{$method_C_function}(
                        $randBuffer, $randLength,
                        $len,
                        $baseLength,
                        $outBuffer
                    );

                    FFI::memset($randBuffer, 0, $randLength);

                    if($c === 0) {
                        $this->usage = 'FFI';

                        //Initialization
                        $this->{$config['bindingEncode']} = [];
                        $this->{$config['bindingDecode']} = $flipped;

                        // //Binding with local variable by reference
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
                        $this->usage = 'PHP_FALLBACK';
                        $this->setWarningMessage("Shuffle in C failed: PHP-Fallback is used for {$config['base']}");
                        $this->shufflePHP($singleBytes, $baseLength, $configName);
                    }
                },
                $len,
                $config['context'],
                $isCustom
            );
        }
    }

    //Shuffle with PHP if C (FFI) is set on disable
    final protected function shufflePHP(array $singleBytes, int $baseLength, string $configName, bool $isCustom = false) : void {

        $config = $this->{$configName};
        if(empty($this->{$config['bindingStr']})) {

            $len = count($singleBytes);

            //Table rejection sampling
            include_once('Const\RejectionSampling.php');

            //Specical role for standard base 100 and 10 convertion
            if($config['checksum'] === true && $baseLength === 10 ) {$baseLength = 100;} 

            //Shuffle within the callable
            $this->_keyStream->derivedKey(
                function($secretBytes) use ($len, $singleBytes, $baseLength, $config) {
                                
                    //Random dervided bytes
                    $randLength = strlen($secretBytes);

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
                            if ($pos >= $randLength) $pos = 0;

                            $byte = ord($secretBytes[$pos++]);
                            if ($byte < REJECTION_SAMPLING[$m]) $val = $byte % $m;
                            $byte = 0;
                        }

                        $tmp = $indices[$i];
                        $indices[$i] = $indices[$val];
                        $indices[$val] = $tmp;
                        $tmp = 0;               
                    }

                    if($baseLength !== $len) {
                        //Grab a new index via Fisher-Yates
                        $index = -1;
                        while ($index < 0) {
                            if ($pos >= $randLength) $pos = 0;

                            $pointer = ord($secretBytes[$pos++]);
                            if ($pointer < REJECTION_SAMPLING[$len]) $index = $pointer % $len;
                        }
                    }
                    else {
                        //Define zero-index
                        $index = 0;
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
                $len,
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