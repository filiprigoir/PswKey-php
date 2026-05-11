<?php declare(strict_types=1);

namespace PswKey\Util\Base;

class Radix {

    private function __construct() {}

    /**
     * Determines if the base is one of the predefined bases
     */
    public static function allowedRadix(int $base) : bool {

        static $allowed = null;
        if($allowed === null) {
            $allowed = [
                10 => true,
                32 => true, 
                58 => true,
                62 => true, 
                64 => true,
                100 => true,
                256 => true
            ];            
        }
        
        return $allowed[$base] ?? false;
    }

    public static function bindRadix(int $radix) : string {
        return str_pad((string)$radix, 3, '0', STR_PAD_LEFT);
    }
}