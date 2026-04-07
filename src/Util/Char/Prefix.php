<?php declare(strict_types=1);

namespace PswKey\Util\Char;

class Prefix {

    private function __construct() {}
    
    public static function byteLength(string $byte) : int {
        $ord = ord($byte);
        if (($ord & 0x80) === 0x00) return 1;  //0xxxxxxx
        if (($ord & 0xE0) === 0xC0) return 2;  //110xxxxx
        if (($ord & 0xF0) === 0xE0) return 3;  //1110xxxx
        if (($ord & 0xF8) === 0xF0) return 4;  //11110xxx
        return 0;
    }
}