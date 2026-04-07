<?php
declare(strict_types=1);

namespace PswKey\Util\Secure;

final class MemeZero {

    private function __construct() {}

    /**
     * Overwrites a single string with zeros in memory.
     */
    public static function overwriteString(?string &$value) : void {
        if(!empty($value)) {
           \sodium_memzero($value);
        }
    }

    /**
     * Overwrites multiple strings stored in an array in memory.
     * The strings must be manually added to the array, e.g. ["string1", "string2"].
     */
    static public function overwriteBulkString(array $array) : void {
        foreach ($array as &$value) {
            self::overwriteString($value);
        }
    }

    /**
     * Overwrites all strings contained in the array and unsets objects.
     * The input must be an instance of array; nested arrays and mixed types are handled safely.
     */   
    static public function overwriteArray(?array &$array) : void {
        if($array !== null) {
            foreach ($array as $key => &$value) {
                if($value !== null) {
                    if(\is_array($value)) { 
                        self::overwriteArray($value);
                    }
                    elseif(\is_string($value)) {
                        self::overwriteString($value);
                        unset($array[$key]);
                    }
                    elseif(\is_integer($value)) {
                        $array[$key] = 0;
                        unset($array[$key]); 
                    }
                    else {
                        unset($array[$key]);
                    }
                }
            }            
        }
    }
}