<?php declare(strict_types=1);

namespace PswKey\Util\Mapping;

class Bits {

    private function __construct() {}

    public static function bits2Bytes(string $bits): string {
        //Lazy initialization
        static $bits2hex = null;
        if ($bits2hex === null) {
            //Table output per 4 bytes
            $bits2hex = [
                '0000' => '0','0001' => '1','0010' => '2','0011' => '3',
                '0100' => '4','0101' => '5','0110' => '6','0111' => '7',
                '1000' => '8','1001' => '9','1010' => 'a','1011' => 'b',
                '1100' => 'c','1101' => 'd','1110' => 'e','1111' => 'f',
            ];
        }
        
        //mapping
        return hex2bin(strtr($bits, $bits2hex));
    }
}