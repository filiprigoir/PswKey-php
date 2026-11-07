<?php
declare(strict_types=1);

namespace PswKey\Core\Component\FFI;

use FFI;
use PswKey\ErrorMessage\InternalMessage;
use PswKey\Exception\ConfigurationException;
use PswKey\Util\Mapping\Merge;

/**
 *  Singleton FFI Static Object
 */
final class InstanceFFI {
    
    private static ?FFI $_shuffleindice = null;
    private static bool $_enabled = false;

    /**
     * Dynamic Controller to generate FFI Singleton
     */
    public static function scope() : ?FFI {
        if(self::$_enabled) {
            $ffi = ini_get('ffi.enable');
            $allowed = '';
            try {
                if(self::$_shuffleindice === null) {
                    $exists = class_exists('FFI');

                    if($exists && $ffi === 'preload') {
                        $allowed = 'FFI & Preload Config';
                        return FFI::scope('SHUFFLEINDICE');
                    }

                    if($exists && $ffi === '1') {
                        //Setup configuration
                        $file = match(PHP_OS_FAMILY) {
                            'Windows' => 'shuffleindice.dll', //Windows
                            'Darwin'  => match (php_uname('m')) {
                                'x86_64' => 'shuffleindice_x86_64.dylib', //old Mac with Intel CPU
                                default => 'shuffleindice.dylib', //new Mac with Apple Silicon
                            },
                            default   => 'shuffleindice.so',  //Linux and other Unix-systems
                        };

                        $allowed = __DIR__ . "/Compiled/{$file}";

                        self::$_shuffleindice = FFI::cdef("
                            int shuffle_indices_secure(
                                const uint8_t *rand_bytes, size_t rand_len,
                                size_t input_len,
                                size_t required_len,
                                uint8_t *out_array
                            );
                        ", $allowed);

                        return self::$_shuffleindice;
                    }
                    else {
                       self::$_enabled = false; 
                    }
                }
                else {
                    return self::$_shuffleindice;
                }
            }
            catch(FFI\Exception $e) {
                self::$_shuffleindice = null;
                self::$_enabled = false;

                throw new ConfigurationException(
                    Merge::string(InternalMessage::INVALID_FFI_PATH, [
                        '%path%' => $allowed
                    ])                    
                );
            }
        }
        return null;
    }

    /**
     * Set the Flag to true to use FFI
     */
    public static function enabled(bool $enable = true) : void {
        self::$_enabled = $enable;
    }

    /**
     * Set the Flag to true to use FFI
     */
    public static function getState() : bool {
        return self::$_enabled;
    }

    /**
     * Clear the FFI Singleton
     */
    public static function clear() : void {
        self::$_shuffleindice = null;
        self::$_enabled = false;
    }
 
    private function __construct() {}
}