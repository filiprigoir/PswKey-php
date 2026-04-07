<?php declare(strict_types=1);

namespace PswKey\Util\Char;

class Eliminate {

    private function __construct() {}
    
    public static function base100Chars() : array {
        $mapTable = null;
        if($mapTable === null) {
            $mapTable = [
                "\x31" => null,"\x32" => null,"\x33" => null,"\x34" => null,"\x35" => null,"\x36" => null,"\x37" => null,
                "\x38" => null,"\x39" => null,"\x41" => null,"\x42" => null,"\x43" => null,"\x44" => null,"\x45" => null,
                "\x46" => null,"\x47" => null,"\x48" => null,"\x4a" => null,"\x4b" => null,"\x4c" => null,"\x4d" => null,
                "\x4e" => null,"\x50" => null,"\x51" => null,"\x52" => null,"\x53" => null,"\x54" => null,"\x55" => null,
                "\x56" => null,"\x57" => null,"\x58" => null,"\x59" => null,"\x5a" => null,"\x61" => null,"\x62" => null,
                "\x63" => null,"\x64" => null,"\x65" => null,"\x66" => null,"\x67" => null,"\x68" => null,"\x69" => null,
                "\x6a" => null,"\x6b" => null,"\x6d" => null,"\x6e" => null,"\x6f" => null,"\x70" => null,"\x71" => null,
                "\x72" => null,"\x73" => null,"\x74" => null,"\x75" => null,"\x76" => null,"\x77" => null,"\x78" => null,
                "\x79" => null,"\x7a" => null,"\x30" => null,"\x49" => null,"\x4f" => null,"\x6c" => null,"\x2f" => null,
                "\x2b" => null,"\x21" => null,"\x22" => null,"\x23" => null,"\x24" => null,"\x25" => null,"\x26" => null,
                "\x27" => null,"\x28" => null,"\x29" => null,"\x2a" => null,"\x2c" => null,"\x2d" => null,"\x2e" => null,
                "\x3a" => null,"\x3b" => null,"\x3d" => null,"\x3f" => null,"\x40" => null,"\x5b" => null,"\x5c" => null,
                "\x5d" => null,"\x5e" => null,"\x5f" => null,"\x60" => null,"\x7b" => null,"\x7c" => null,"\x7d" => null,
                "\x7e" => null,"\xa3" => null,"\xa7" => null,"\xa8" => null,"\xb2" => null,"\xb3" => null,"\xb4" => null,
                "\xb5" => null,"\xb0" => null
            ];
        }
        return $mapTable;
    }
}