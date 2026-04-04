<?php declare(strict_types=1);

namespace PswKey\Util\Base;

class CheckBase {

    private function __construct() {}

    public static function defaultShuffle(int $base) : bool {

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
}